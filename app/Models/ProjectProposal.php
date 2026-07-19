<?php

namespace App\Models;

use App\Enums\ProjectProposalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ProjectProposal extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;

    /**
     * Größe des aktuellen Vorstands laut Konfiguration.
     */
    public static function boardSize(): int
    {
        return count(config('einundzwanzig.config.current_board', []));
    }

    /**
     * Stimmen, ab denen ein Antrag als angenommen bzw. abgelehnt gilt: die
     * absolute Mehrheit des Vorstands.
     *
     * Bewusst abgeleitet statt als Konstante — der Vorstand wächst, und eine
     * fest verdrahtete 3 wäre bei sieben Mitgliedern keine Mehrheit mehr,
     * sondern eine Minderheit, die Anträge entscheidet.
     */
    public static function boardVoteThreshold(): int
    {
        return intdiv(static::boardSize(), 2) + 1;
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
        'support_in_sats',
        'website',
        'contact_via_nostr_dm',
        'contact_alternative',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'einundzwanzig_pleb_id' => 'integer',
        'accepted' => 'boolean',
        'sats_paid' => 'integer',
        'contact_via_nostr_dm' => 'boolean',
    ];

    protected static function booted() {}

    /**
     * IDs aller Plebs, die im aktuellen Vorstand sitzen.
     *
     * Die Vorstandszugehörigkeit hängt am npub in der Konfiguration, nicht an einer
     * Spalte. Einmal pro Request aufgelöst, damit die Stimm-Aggregate ohne
     * verschachteltes whereHas auf die Profil-Tabelle auskommen.
     *
     * @return list<int>
     */
    public static function boardPlebIds(): array
    {
        static $ids = null;

        if ($ids === null) {
            $ids = EinundzwanzigPleb::query()
                ->whereIn('npub', config('einundzwanzig.config.current_board', []))
                ->pluck('id')
                ->all();
        }

        return $ids;
    }

    /**
     * Hängt die Stimm-Aggregate an, die Karten und Status brauchen — ohne die
     * Stimmen selbst zu laden.
     */
    public function scopeWithVoteAggregates(Builder $query): Builder
    {
        $boardPlebIds = static::boardPlebIds();

        return $query->withCount([
            'votes as board_approvals_count' => fn (Builder $q) => $q
                ->where('value', true)
                ->whereIn('einundzwanzig_pleb_id', $boardPlebIds),
            'votes as board_rejections_count' => fn (Builder $q) => $q
                ->where('value', false)
                ->whereIn('einundzwanzig_pleb_id', $boardPlebIds),
            'votes as supporters_count' => fn (Builder $q) => $q->where('value', true),
        ]);
    }

    /**
     * Markiert je Antrag, ob der angegebene Pleb bereits abgestimmt hat — als
     * Unterabfrage statt als geladene Relation, damit die Übersicht keine
     * einzige Vote-Zeile anfassen muss.
     */
    public function scopeWithOwnVote(Builder $query, ?int $plebId): Builder
    {
        if ($plebId === null) {
            return $query;
        }

        return $query->withExists([
            'votes as has_own_vote' => fn (Builder $q) => $q->where('einundzwanzig_pleb_id', $plebId),
        ]);
    }

    /**
     * Schränkt auf einen abgeleiteten Status ein. 'all' lässt die Query unberührt.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        $boardPlebIds = static::boardPlebIds();
        $threshold = static::boardVoteThreshold();

        $boardVotes = fn (bool $value) => fn (Builder $q) => $q
            ->where('value', $value)
            ->whereIn('einundzwanzig_pleb_id', $boardPlebIds);

        return match ($status) {
            ProjectProposalStatus::Supported->value => $query->where('sats_paid', '>', 0),
            ProjectProposalStatus::Rejected->value => $query
                ->where(fn (Builder $q) => $q->whereNull('sats_paid')->orWhere('sats_paid', '<=', 0))
                ->whereHas('votes', $boardVotes(false), '>=', $threshold),
            ProjectProposalStatus::Accepted->value => $query
                ->where(fn (Builder $q) => $q->whereNull('sats_paid')->orWhere('sats_paid', '<=', 0))
                ->whereHas('votes', $boardVotes(true), '>=', $threshold)
                ->whereHas('votes', $boardVotes(false), '<', $threshold),
            ProjectProposalStatus::InVoting->value => $query
                ->where(fn (Builder $q) => $q->whereNull('sats_paid')->orWhere('sats_paid', '<=', 0))
                ->whereHas('votes', $boardVotes(true), '<', $threshold)
                ->whereHas('votes', $boardVotes(false), '<', $threshold),
            default => $query,
        };
    }

    /**
     * Volltextsuche über Name, Beschreibung und den Namen des Einreichers.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->whereLike('name', '%'.$term.'%')
                ->orWhereLike('description', '%'.$term.'%')
                ->orWhereHas('einundzwanzigPleb.profile', fn (Builder $p) => $p->whereLike('name', '%'.$term.'%'));
        });
    }

    /**
     * Der abgeleitete Status. Setzt geladene Aggregate voraus (scopeWithVoteAggregates);
     * fällt sonst auf die geladenen Stimmen zurück.
     */
    public function status(): ProjectProposalStatus
    {
        if ($this->sats_paid > 0) {
            return ProjectProposalStatus::Supported;
        }

        if ($this->boardRejections() >= static::boardVoteThreshold()) {
            return ProjectProposalStatus::Rejected;
        }

        if ($this->boardApprovals() >= static::boardVoteThreshold()) {
            return ProjectProposalStatus::Accepted;
        }

        return ProjectProposalStatus::InVoting;
    }

    public function boardApprovals(): int
    {
        return (int) ($this->board_approvals_count ?? $this->countBoardVotes(true));
    }

    public function boardRejections(): int
    {
        return (int) ($this->board_rejections_count ?? $this->countBoardVotes(false));
    }

    public function supporters(): int
    {
        return (int) ($this->supporters_count ?? $this->votes->where('value', true)->count());
    }

    protected function countBoardVotes(bool $value): int
    {
        $boardPlebIds = static::boardPlebIds();

        return $this->votes
            ->where('value', $value)
            ->whereIn('einundzwanzig_pleb_id', $boardPlebIds)
            ->count();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->saveSlugsTo('slug')
            ->usingLanguage(Cookie::get('lang', config('app.locale')));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();
        $this
            ->addMediaConversion('thumb')
            ->fit(Fit::Crop, 130, 130)
            ->width(130)
            ->height(130);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('main')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ])
            ->useDisk('private')
            ->useFallbackUrl(asset('einundzwanzig-alpha.jpg'));
    }

    public function getSignedMediaUrl(string $collection = 'main', int $expireMinutes = 60, ?string $conversion = null): string
    {
        $media = $this->getFirstMedia($collection);
        if (! $media) {
            return asset('einundzwanzig-alpha.jpg');
        }

        $parameters = ['media' => $media];

        if ($conversion && $media->hasGeneratedConversion($conversion)) {
            $parameters['conversion'] = $conversion;
        }

        return url()->temporarySignedRoute('media.signed', now()->addMinutes($expireMinutes), $parameters);
    }

    public function einundzwanzigPleb(): BelongsTo
    {
        return $this->belongsTo(EinundzwanzigPleb::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Nutzt das Aggregat aus scopeWithOwnVote(), wenn es geladen ist, und fällt
     * sonst auf die geladenen Stimmen zurück.
     */
    public function hasVoteFrom(EinundzwanzigPleb $pleb): bool
    {
        if ($this->has_own_vote !== null) {
            return (bool) $this->has_own_vote;
        }

        return $this->votes->contains('einundzwanzig_pleb_id', $pleb->id);
    }
}
