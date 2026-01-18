<?php

namespace App\Livewire;

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

class EinundzwanzigPlebTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'einundzwanzig-pleb-table';

    public string $sortField = 'association_status';

    public string $sortDirection = 'desc';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage(0)
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.detail')
                ->showCollapseIcon()
                ->params([]),
        ];
    }

    public function datasource(): Builder
    {
        return EinundzwanzigPleb::query()
            ->with([
                'profile',
                'paymentEvents' => fn ($query) => $query
                    ->where('year', date('Y'))
                    ->where('paid', true),
            ])
            ->where('association_status', '>', 1)
            ->orWhereNotNull('application_for');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('pubkey')
            ->add(
                'avatar',
                fn ($model,
                ) => '<img class="w-8 h-8 shrink-0 grow-0 rounded-full" onerror="this.onerror=null; this.src=\'https://robohash.org/test\'";" src="'.asset(
                    $model->profile?->picture,
                ).'">',
            )
            ->add(
                'for',
                fn ($model,
                ) => $model->application_for ? '<div class="m-1.5"><div class="text-xs inline-flex font-medium bg-red-500/20 text-red-700 rounded-full text-center px-2.5 py-1">'.AssociationStatus::from(
                    $model->application_for,
                )->label().'</div></div>' : '',
            )
            ->add(
                'payment',
                fn (EinundzwanzigPleb $model) => $model->paymentEvents->count() > 0 && $model->paymentEvents->first()->paid ? '<span class="text-green-500">'.number_format(
                    $model->paymentEvents->first()->amount,
                    0,
                    ',',
                    '.',
                ).'</span>' : 'keine Zahlung vorhanden',
            )
            ->add('npub_export', fn (EinundzwanzigPleb $model) => $model->npub)
            ->add(
                'npub',
                fn (EinundzwanzigPleb $model) => '<a target="_blank" class="btn-xs bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white" href="https://nostrudel.ninja/u/'.e(
                    $model->npub,
                ).'">Nostr Profile</a>',
            )
            ->add('association_status')
            ->add('association_status_name', fn (EinundzwanzigPleb $model) => $model->association_status->name)
            ->add('paid_export', fn (EinundzwanzigPleb $model) => $model->paymentEvents->first()?->amount)
            ->add(
                'association_status_formatted',
                function (EinundzwanzigPleb $model) {
                    $class = match ($model->association_status) {
                        AssociationStatus::DEFAULT => 'text-xs inline-flex font-medium rounded-full text-center px-2.5 py-1 bg-gray-500/20 text-gray-200',
                        AssociationStatus::PASSIVE => 'text-xs inline-flex font-medium rounded-full text-center px-2.5 py-1 bg-yellow-500/20 text-yellow-700',
                        AssociationStatus::ACTIVE => 'text-xs inline-flex font-medium rounded-full text-center px-2.5 py-1 bg-green-500/20 text-green-700',
                        AssociationStatus::HONORARY => 'text-xs inline-flex font-medium rounded-full text-center px-2.5 py-1 bg-blue-500/20 text-blue-700',
                        default => 'text-xs inline-flex font-medium rounded-full text-center px-2.5 py-1 text-red-700',
                    };

                    return '<span class="'.$class.'">'.$model->association_status->label().'</span>';
                },
            )
            ->add(
                'name_lower',
                fn (EinundzwanzigPleb $model) => strtolower(
                    e($model->profile?->name ?: $model->profile?->display_name ?? ''),
                ),
            );
    }

    public function columns(): array
    {
        return [
            Column::make('Avatar', 'avatar')
                ->visibleInExport(visible: false),

            Column::make('Npub', 'npub')
                ->visibleInExport(visible: false)
                ->sortable(),

            Column::make('Name', 'name_lower')
                ->visibleInExport(visible: true)
                ->sortable(),

            Column::make('Aktueller Status', 'association_status_formatted', 'association_status')
                ->visibleInExport(visible: false)
                ->sortable(),

            Column::make('Beitrag '.date('Y'), 'payment')
                ->visibleInExport(visible: false),

            Column::make('Bewirbt sich für', 'for', 'application_for')
                ->visibleInExport(visible: false)
                ->sortable(),

            Column::action('Action')
                ->visibleInExport(visible: false),

            Column::make('Email', 'email')
                ->hidden()
                ->visibleInExport(visible: true),

            Column::make('Status', 'association_status_name')
                ->hidden()
                ->visibleInExport(visible: true),

            Column::make('Npub', 'npub_export')
                ->hidden()
                ->visibleInExport(visible: true),

            Column::make('Bezahlt', 'paid_export')
                ->hidden()
                ->visibleInExport(visible: true),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public ?int $confirmAcceptId = null;

    public ?int $confirmDeleteId = null;

    #[\Livewire\Attributes\On('accept')]
    public function accept($rowId): void
    {
        $this->confirmAcceptId = $rowId;
        $this->modal('confirm-accept-pleb')->show();
    }

    #[\Livewire\Attributes\On('delete')]
    public function delete($rowId): void
    {
        $this->confirmDeleteId = $rowId;
        $this->modal('confirm-delete-pleb')->show();
    }

    public function acceptPleb(): void
    {
        if ($this->confirmAcceptId) {
            $pleb = EinundzwanzigPleb::query()->findOrFail($this->confirmAcceptId);
            $for = $pleb->application_for;
            $text = $pleb->application_text;
            $pleb->association_status = AssociationStatus::from($for);
            $pleb->application_for = null;
            $pleb->archived_application_text = $text;
            $pleb->application_text = null;
            $pleb->save();

            $this->confirmAcceptId = null;
            $this->modal('confirm-accept-pleb')->close();
            $this->fillData();
        }
    }

    public function deletePleb(): void
    {
        if ($this->confirmDeleteId) {
            $pleb = EinundzwanzigPleb::query()->findOrFail($this->confirmDeleteId);
            $pleb->application_for = null;
            $pleb->application_text = null;
            $pleb->save();

            $this->confirmDeleteId = null;
            $this->modal('confirm-delete-pleb')->close();
            $this->fillData();
        }
    }

    public function actions(EinundzwanzigPleb $row): array
    {
        return [
            Button::add('delete')
                ->slot('Löschen')
                ->id()
                ->class('px-3 py-1.5 text-sm font-medium rounded-md border transition-colors bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-950')
                ->dispatch('delete', ['rowId' => $row->id]),
            Button::add('accept')
                ->slot('Akzeptieren')
                ->id()
                ->class('px-3 py-1.5 text-sm font-medium rounded-md border transition-colors bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-emerald-600 dark:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950')
                ->dispatch('accept', ['rowId' => $row->id]),
        ];
    }

    public function actionRules(EinundzwanzigPleb $row): array
    {
        return [
            // Hide button edit for ID 1
            Rule::button('accept')
                ->when(fn ($row) => $row->application_for === null)
                ->hide(),
        ];
    }
}
