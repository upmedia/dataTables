<?php

namespace App\Http\Controllers\DataTable;

use App\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\DataTable\DataTableController;

class PlanController extends DataTableController
{
    public function builder()
    {
    	return Plan::query();
    }

    public function getUpdatableColumns()
    {
        return [
            'braintree_id', 'price', 'active', 'updated_at'
        ];
    }

    /**
     * Create a new plan record
     * @param  Request $request
     * @return void
     */
    public function store(Request $request)
    {
        if (!$this->allowCreation) {
            return;
        }

        $this->validate($request, [
            'braintree_id' => 'required',
            'price' => 'required',
        ]);

        $this->builder->create($request->only($this->getUpdatableColumns()));
    }

}
