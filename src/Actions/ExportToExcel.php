<?php

namespace Maatwebsite\LaravelNovaExcel\Actions;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Bus\PendingDispatch;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Exceptions\MissingActionHandlerException;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\ActionRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\LaravelNovaExcel\Concerns\WithDisk;
use Maatwebsite\LaravelNovaExcel\Concerns\WithFilename;
use Maatwebsite\LaravelNovaExcel\Concerns\WithWriterType;
use Maatwebsite\LaravelNovaExcel\Interactions\AskForFilename;
use Maatwebsite\LaravelNovaExcel\Requests\ExportActionRequest;

class ExportToExcel extends Action implements FromQuery
{
    use AskForFilename,
        WithDisk,
        WithFilename,
        WithWriterType;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var Field[]
     */
    protected $actionFields;

    /**
     * Execute the action for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\ActionRequest $request
     *
     * @return mixed
     */
    public function handleRequest(ActionRequest $request)
    {
        $this->handleFilename($request);

        $method = ActionMethod::determine($this, $request->targetModel());
        if (!method_exists($this, $method)) {
            throw MissingActionHandlerException::make($this, $method);
        }

        $query = ExportActionRequest::createFrom($request)->getExportQuery();

        $response = Excel::store(
            $this->withQuery($query),
            $this->getFilename(),
            $this->getDisk(),
            $this->getWriterType()
        );

        return $this->{$method}($response);
    }

    /**
     * @param bool|PendingDispatch $response
     *
     * @return array
     */
    public function handle($response)
    {
        if (false === $response) {
            return Action::danger(__('Resource could not be exported.'));
        }

        return Action::message(__('Resource was successfully exported.'));
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function withName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @param Builder $query
     *
     * @return $this
     */
    protected function withQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return string
     */
    protected function getDefaultExtension(): string
    {
        return $this->getWriterType() ? strtolower($this->getWriterType()) : 'xlsx';
    }

    /**
     * @return Field[]
     */
    public function fields()
    {
        return $this->actionFields;
    }

    /**
     * Remove all attributes from this class when serializing,
     * so the action can be queued as exportable.
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }
}