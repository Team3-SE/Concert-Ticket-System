<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

/**
 * ModelTesterController
 * Dev-only controller to test Eloquent model methods via HTTP
 * Author : AI Agent
 * Created : 30/08/2026
 */
class ModelTesterController extends Controller
{
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
        $className = "App\\Models\\" . $model;

        if (!class_exists($className)) {
            return $this->corsResponse([]);
        }

        $reflector = new ReflectionClass($className);

        // Always add common Eloquent static methods
        $methods = [
            ['name' => 'all',   'params' => [],     'type' => 'static'],
            ['name' => 'find',  'params' => ['id'], 'type' => 'static'],
            ['name' => 'first', 'params' => [],     'type' => 'static'],
            ['name' => 'count', 'params' => [],     'type' => 'static'],
        ];

        // Add methods defined directly on this model class
        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class === $className) {
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

        $modelClass = "App\\Models\\" . $request->input('model');
        $methodName = $request->input('method_name');
        $params     = array_values($request->input('params', []));
        $instanceId = $request->input('instance_id');
        $type       = $request->input('type');

        if (!class_exists($modelClass)) {
            return $this->corsResponse(['success' => false, 'error' => 'Model not found: ' . $modelClass], 404);
        }

        try {
            if ($type === 'static') {
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

                // If result is a relation, resolve it
                if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $result = $result->get();
                }
            }

            return $this->corsResponse(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return $this->corsResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}