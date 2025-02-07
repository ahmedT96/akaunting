<?php

namespace App\BulkActions\Incomes;

use App\Abstracts\BulkAction;
use App\Exports\Incomes\Revenues as Export;
use App\Models\Banking\Transaction;
use App\Models\Setting\Category;

class Revenues extends BulkAction
{
    public $model = Transaction::class;

    public $actions = [
        'export' => [
            'name' => 'general.export',
            'message' => 'bulk_actions.message.exports',
        ],
        'duplicate' => [
            'name' => 'general.duplicate',
            'message' => 'bulk_actions.message.duplicate',
            'permission' => 'create-incomes-revenues',
            'multiple' => true
        ],
        'delete' => [
            'name' => 'general.delete',
            'message' => 'bulk_actions.message.deletes',
            'permission' => 'delete-incomes-revenues'
        ]
    ];

    public function duplicate($request)
    {
        $selected = $request->get('selected', []);

        $transactions = $this->model::find($selected);

        foreach ($transactions as $transaction) {
            $clone = $transaction->duplicate();
        }
    }

    public function delete($request)
    {
        $this->destroy($request);
    }

    public function destroy($request)
    {
        $selected = $request->get('selected', []);

        $transactions = $this->model::find($selected);

        foreach ($transactions as $transaction) {
            if ($transaction->category->id != Category::transfer()) {
                $type = $transaction->type;

                $transaction->recurring()->delete();
                $transaction->delete();

                $message = trans('messages.success.deleted', ['type' => trans_choice('general.' . \Str::plural($type), 1)]);

                flash($message)->success();
            } else {
                $this->response->errorUnauthorized();
            }
        }
    }

    public function export($request)
    {
        $selected = $request->get('selected', []);

        return \Excel::download(new Export($selected), trans_choice('general.revenues', 2) . '.xlsx');
    }
}
