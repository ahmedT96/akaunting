<?php

namespace App\BulkActions\Banking;

use App\Abstracts\BulkAction;
use App\Models\Banking\Reconciliation;
use App\Models\Banking\Transaction;

class Reconciliations extends BulkAction
{
    public $model = Reconciliation::class;

    public $actions = [
        'enable' => [
            'name' => 'general.enable',
            'message' => 'bulk_actions.message.enable',
            'permission' => 'update-banking-reconciliations'
        ],
        'disable' => [
            'name' => 'general.disable',
            'message' => 'bulk_actions.message.disable',
            'permission' => 'update-banking-reconciliations'
        ],
        'delete' => [
            'name' => 'general.delete',
            'message' => 'bulk_actions.message.deletes',
            'permission' => 'delete-banking-reconciliations'
        ]
    ];

    public function enable($request)
    {
        $selected = $request->get('selected', []);

        $reconciliations = $this->model::find($selected);

        foreach ($reconciliations as $reconciliation) {
            $reconciliation->enabled = 1;
            $reconciliation->save();

            Transaction::where('account_id', $reconciliation->account_id)
                ->reconciled()
                ->whereBetween('paid_at', [$reconciliation->started_at, $reconciliation->ended_at])->each(function ($item) {
                    $item->reconciled = 1;
                    $item->save();
                });
        }
    }

    public function disable($request)
    {
        $selected = $request->get('selected', []);

        $reconciliations = $this->model::find($selected);

        foreach ($reconciliations as $reconciliation) {
            $reconciliation->enabled = 0;
            $reconciliation->save();

            Transaction::where('account_id', $reconciliation->account_id)
                ->reconciled()
                ->whereBetween('paid_at', [$reconciliation->started_at, $reconciliation->ended_at])->each(function ($item) {
                    $item->reconciled = 0;
                    $item->save();
                });
        }
    }

    public function delete($request)
    {
        $this->destroy($request);
    }

    public function destroy($request)
    {
        $selected = $request->get('selected', []);

        $reconciliations = $this->model::find($selected);

        foreach ($reconciliations as $reconciliation) {
            $reconciliation->delete();

            Transaction::where('account_id', $reconciliation->account_id)
                ->reconciled()
                ->whereBetween('paid_at', [$reconciliation->started_at, $reconciliation->ended_at])->each(function ($item) {
                    $item->reconciled = 0;
                    $item->save();
                });
        }
    }
}
