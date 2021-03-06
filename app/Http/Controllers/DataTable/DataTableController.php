<?php

namespace App\Http\Controllers\DataTable;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

abstract class DataTableController extends Controller
{
   /**
    * If an entity is allowed to be created.
    * @var boolean
    */
    protected $allowCreation = true;

    /**
     * If entity is allowed to be deleted.
     * @var boolean
     */
    protected $allowDeletion = true;

	protected $builder;

    abstract public function builder();

    public function __construct()
    {
    	$builder = $this->builder();


    	if (!$builder instanceof Builder) {
    		throw new \Exception('Not instance of Builder');
    	}

    	$this->builder = $builder;
    }

    public function index(Request $request)
    {
    	return response()->json([
    		'data' => [
    			'table' => $this->builder->getModel()->getTable(),
    			'displayable' => array_values($this->getDisplayableColumns()),
    			'updateable' => array_values($this->getUpdatableColumns()),
    			'records' => $this->getRecords($request),
                'allow' => [
                    'creation' => $this->allowCreation,
                    'deletion' => $this->allowDeletion,
                ],
    		]
    	]);

    }

    /**
     * Create a new record
     * @param  Request $request
     * @return void
     */
    public function store(Request $request)
    {
        if (!$this->allowCreation) {
            return;
        }

        $this->builder->create($request->only($this->getUpdatableColumns()));
    }

    public function destroy($id, Request $request) {
        if (!$this->allowDeletion) {
            return;
        }

        $this->builder->find($id)->delete();
    }

    public function getDisplayableColumns()
    {
    	return array_diff($this->getDatabaseColumnNames(), $this->builder->getModel()->getHidden());
    }

    public function getUpdatableColumns()
    {
    	return $this->getDisplayableColumns();
    }

    protected function getDatabaseColumnNames()
    {
    	return Schema::getColumnListing($this->builder->getModel()->getTable());
    }

    protected function getRecords(Request $request)
    {
        $builder = $this->builder;

        if ($this->hasSearchQuery($request)) {
            $builder = $this->buildSearch($builder, $request);
        }

        try {
            return  $this->builder->limit(request()->limit)->orderBy('id', 'asc')->get($this->getDisplayableColumns());
        } catch(QueryException $e) {
            return [];
        }


    }

    protected function hasSearchQuery(Request $request)
    {
        return count(array_filter($request->only(['column', 'operator', 'value']))) === 3;
    } // hasSearchQuery()

    protected function buildSearch(Builder $builder, Request $request)
    {
        $queryParts = $this->resolveQueryParts($request->operator, $request->value);

        return $builder->where($request->column, $queryParts['operator'], $queryParts['value']);
    }

    protected function resolveQueryParts($operator, $value)
    {
        return array_get([
            'equals' => [
                'operator' => '=',
                'value' => $value
            ],
            'contains' => [
                'operator' => 'LIKE',
                'value' => "%{$value}%"
            ],
            'starts_with' => [
                'operator' => 'LIKE',
                'value' => "{$value}%"
            ],
            'ends_with' => [
                'operator' => 'LIKE',
                'value' => "%{$value}"
            ],
            'greater_than' => [
                'operator' => '>',
                'value' => $value
            ],
            'less_than' => [
                'operator' => '<',
                'value' => $value
            ],
        ], $operator);
    }


}
