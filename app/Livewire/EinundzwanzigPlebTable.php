<?php

namespace App\Livewire;

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Detail;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class EinundzwanzigPlebTable extends PowerGridComponent
{
    public string $sortField = 'application_for';

    public string $sortDirection = 'asc';

    public bool $multiSort = true;

    public function setUp(): array
    {
        return [
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage(25)
                ->showRecordCount(),
            Detail::make()
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
                fn($model,
                )
                    => '<img class="w-8 h-8 shrink-0 grow-0 rounded-full" onerror="this.onerror=null; this.src=\'https://robohash.org/test\'";" src="' . asset(
                        $model->profile?->picture,
                    ) . '">',
            )
            ->add(
                'for',
                fn($model,
                )
                    => $model->application_for ? '<div class="m-1.5"><div class="text-xs inline-flex font-medium bg-red-500/20 text-red-700 rounded-full text-center px-2.5 py-1">' . AssociationStatus::from(
                        $model->application_for,
                    )->label() . '</div></div>' : '',
            )
            ->add(
                'npub',
                fn(EinundzwanzigPleb $model)
                    => '<a target="_blank" class="btn-xs bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white" href="https://next.nostrudel.ninja/#/u/' . e(
                        $model->npub,
                    ) . '">Nostr Profile</a>',
            )
            ->add(
                'association_status_formatted',
                fn(EinundzwanzigPleb $model)
                    => $model->association_status->label(),
            )
            ->add(
                'name_lower',
                fn(EinundzwanzigPleb $model)
                    => strtolower(
                    e($model->profile?->name ?? $model->profile?->display_name ?? ''),
                ),
            );
    }

    public function columns(): array
    {
        return [
            Column::make('Avatar', 'avatar'),

            Column::make('Npub', 'npub')
                ->searchable()
                ->sortable(),

            Column::make('Name', 'name_lower')
                ->searchable()
                ->sortable(),

            Column::make('Aktueller Status', 'association_status_formatted', 'association_status')
                ->searchable()
                ->sortable(),

            Column::make('Bewirbt sich für', 'for', 'application_for')
                ->searchable()
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('name'),
            Filter::datepicker('created_at_formatted', 'created_at'),
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $pleb = EinundzwanzigPleb::query()->findOrFail($rowId);
        $for = $pleb->application_for;
        $pleb->association_status = AssociationStatus::from($for);
        $pleb->application_for = null;
        $pleb->application_text = null;
        $pleb->save();

        $this->fillData();
    }

    public function actions(EinundzwanzigPleb $row): array
    {
        return [
            Button::add('edit')
                ->slot('Approve')
                ->id()
                ->class(
                    'btn bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-green-500',
                )
                ->dispatch('edit', ['rowId' => $row->id]),
        ];
    }

    public function actionRules(EinundzwanzigPleb $row): array
    {
        return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => false)
                ->hide(),
        ];
    }

}
