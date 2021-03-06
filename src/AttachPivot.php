<?php
namespace NovaAttachPivot;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Authorizable;
use NovaAttachPivot\Rules\ArrayRules;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class AttachPivot extends Field
{
    use Authorizable;
 
    public $height = '300px';

    public $fullWidth = false;

    public $showToolbar = true;

    public $showCounts = false;

    public $showPreview = false;

    public $showOnIndex = false;

    public $showOnDetail = false;

    /**
     * The callback that should be used to resolve the pivot fields.
     *
     * @var callable
     */
    public $pivotFields;

    public $searchableFields;

    public $component = 'nova-attach-pivot';

    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resource = $resource;

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;

        $this->pivotFields = function () {
            return '';
        };

        $this->searchableFields = function () {
            return '';
        };

        $this->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($resource) {
            if (is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
                $model::saved(function ($model) use ($attribute, $request, $requestAttribute) {
                    $response = json_decode($request->$attribute, true);

                    $sync = [];
                    if(is_array($response)){
                        foreach($response as $element){
                            $pivots = [];

                            foreach ($element["pivots"] as $pivot) {
                                $pivots[Str::lower($pivot["display"])] = $pivot["value"] ?: null;
                            }

                            if (!empty($pivots)) {
                                $sync[$element["value"]] = $pivots;
                            }

                        }

                        if (!empty($sync)) {
                            $model->$attribute()->sync($sync, true);
                        }
                    }
                    
                });

                unset($request->$attribute);
            }
        });
    }

    public function rules($rules)
    {
        $rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        $this->rules = [ new ArrayRules($rules) ];

        return $this;
    }

    public function resolve($resource, $attribute = null)
    {
        $this->withMeta([
            'height' => $this->height,
            'fullWidth' => $this->fullWidth,
            'showCounts' => $this->showCounts,
            'showPreview' => $this->showPreview,
            'showToolbar' => $this->showToolbar,
            'pivotFields' => $this->pivotFields,
            'searchableFields' => $this->searchableFields
        ]);
    }

    public function authorize(Request $request)
    {
        if (! $this->resourceClass::authorizable()) {
            return true;
        }

        if (! isset($request->resource)) {
            return false;
        }

        return call_user_func([ $this->resourceClass, 'authorizedToViewAny'], $request)
            && $request->newResource()->authorizedToAttachAny($request, $this->resourceClass::newModel())
            && parent::authorize($request);
    }

    public function height($height)
    {
        $this->height = $height;

        return $this;
    }

    public function fullWidth($fullWidth=true)
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    public function hideToolbar()
    {
        $this->showToolbar = false;

        return $this;
    }

    public function showCounts($showCounts=true)
    {
        $this->showCounts = $showCounts;

        return $this;
    }

    public function showPreview($showPreview=true)
    {
        $this->showPreview = $showPreview;

        return $this;
    }

    public function pivotFields($pivotFields)
    {
        $this->pivotFields = is_array($pivotFields) ? $pivotFields : [$pivotFields];

        return $this;
    }

    public function searchableFields($searchableFields)
    {
        $this->searchableFields = is_array($searchableFields) ? $searchableFields : [$searchableFields];

        return $this;
    }

}
