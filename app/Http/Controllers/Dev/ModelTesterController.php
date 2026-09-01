<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * ModelTesterController
 * Dev-only controller to test Eloquent model methods via HTTP
 * Author : AI Agent
 * Created : 30/08/2026
 */
class ModelTesterController extends Controller
{
    private const MODEL_ALIASES = [
        'User' => 'AppUser',
    ];

    private const HIDDEN_METHODS = [
        'getKeyType',
        'getIncrementing',
        'newUniqueId',
        'uniqueIds',
        'initializeHasUniqueStringIds',
        'resolveRouteBindingQuery',
    ];

    /**
     * corsResponse(data, status)
     * Return a JSON response with CORS headers.
     * param : data, status
     * return : JsonResponse
     */
    private function corsResponse($data, $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }

    /**
     * getMethods(request)
     * Return list of public methods on a given Model class.
     * param : request
     * return : JsonResponse
     */
    public function getMethods(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->corsResponse(['status' => 'ok']);
        }

        $model = $request->input('model');
        if (empty($model)) {
            return $this->corsResponse(['debug_error' => 'No model provided', 'all_inputs' => $request->all()]);
        }
        $className = $this->resolveModelClass($model);

        if (!class_exists($className)) {
            return $this->corsResponse([]);
        }

        $reflector = new ReflectionClass($className);
        $instance = $reflector->newInstance();
        $primaryKey = $instance instanceof Model ? $instance->getKeyName() : null;
        $hasSinglePrimaryKey = !empty($primaryKey);

        $methods = [
            ['name' => 'all',   'params' => [],     'type' => 'static'],
            ['name' => 'first', 'params' => [],     'type' => 'static'],
            ['name' => 'count', 'params' => [],     'type' => 'static'],
        ];

        if ($hasSinglePrimaryKey) {
            $methods[] = ['name' => 'find', 'params' => [$primaryKey], 'type' => 'static'];
        }

        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class === $className) {
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                if ($method->getFileName() !== $reflector->getFileName()) {
                    continue;
                }

                if (in_array($method->getName(), self::HIDDEN_METHODS, true)) {
                    continue;
                }

                if (!$method->isStatic() && !$hasSinglePrimaryKey) {
                    continue;
                }

                $params = [];
                foreach ($method->getParameters() as $param) {
                    $params[] = $param->getName();
                }
                $methods[] = [
                    'name'   => $method->getName(),
                    'params' => $params,
                    'type'   => $method->isStatic() ? 'static' : 'instance',
                ];
            }
        }

        return $this->corsResponse($methods);
    }

    /**
     * execute(request)
     * Execute the specified method on the given Model and return result.
     * param : request
     * return : JsonResponse
     */
    public function execute(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->corsResponse(['status' => 'ok']);
        }

        $modelClass = $this->resolveModelClass($request->input('model'));
        $methodName = $request->input('method_name');
        $params     = array_values($request->input('params', []));
        $instanceId = $request->input('instance_id');
        $type       = $request->input('type');

        if (!class_exists($modelClass)) {
            return $this->corsResponse(['success' => false, 'error' => 'Model not found: ' . $modelClass], 404);
        }

        try {
            $commonStaticMethods = ['all', 'find', 'first', 'count'];
            $isCommonStaticMethod = $type === 'static' && in_array($methodName, $commonStaticMethods, true);

            if (empty($methodName) || (!$isCommonStaticMethod && !method_exists($modelClass, $methodName))) {
                return $this->corsResponse([
                    'success' => false,
                    'error' => 'Method not found: ' . $methodName,
                ], 404);
            }

            if ($type === 'static') {
                if ($methodName === 'find' && count($params) === 0) {
                    $instance = new $modelClass();
                    $primaryKey = $instance instanceof Model ? $instance->getKeyName() : 'id';

                    return $this->corsResponse([
                        'success' => false,
                        'error' => "Please provide {$primaryKey} to call find().",
                    ], 400);
                }

                $result = $modelClass::$methodName(...$params);
            } else {
                if (!$instanceId) {
                    return $this->corsResponse(['success' => false, 'error' => 'Please provide Instance ID (Primary Key) to call an instance method.'], 400);
                }
                $instance = $modelClass::find($instanceId);
                if (!$instance) {
                    return $this->corsResponse(['success' => false, 'error' => 'Record not found for ID: ' . $instanceId], 404);
                }
                $result = $instance->$methodName(...$params);

                if ($result instanceof Relation) {
                    $result = $result->get();
                }
            }

            return $this->corsResponse(['success' => true, 'data' => $result]);
        } catch (Throwable $e) {
            return $this->corsResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ], 500);
        }
    }

    private function resolveModelClass(?string $model): string
    {
        $model = trim((string) $model);
        $model = ltrim($model, '\\');
        $model = str_starts_with($model, 'App\\Models\\') ? substr($model, 11) : $model;
        $model = self::MODEL_ALIASES[$model] ?? $model;

        return "App\\Models\\" . $model;
    }
}
