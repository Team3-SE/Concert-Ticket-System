<?php
namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Facades\File;

class ModelTesterController extends Controller
{
    private function corsResponse($data, $status = 200) {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }

    public function getMethods(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->corsResponse(['status' => 'ok']);
        }

        $model = $request->model;
        $className = "App\\Models\\" . $model;
        
        if (!class_exists($className)) return $this->corsResponse([]);

        $reflector = new ReflectionClass($className);
        $methods = [];
        
        $methods[] = ['name' => 'all', 'params' => [], 'type' => 'static'];
        $methods[] = ['name' => 'find', 'params' => ['id'], 'type' => 'static'];
        $methods[] = ['name' => 'first', 'params' => [], 'type' => 'static'];
        $methods[] = ['name' => 'count', 'params' => [], 'type' => 'static'];

        foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class === $className) {
                $params = [];
                foreach ($method->getParameters() as $param) {
                    $params[] = $param->getName();
                }
                $methods[] = [
                    'name' => $method->getName(),
                    'params' => $params,
                    'type' => $method->isStatic() ? 'static' : 'instance'
                ];
            }
        }
        return $this->corsResponse($methods);
    }

    public function execute(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->corsResponse(['status' => 'ok']);
        }

        $model = "App\\Models\\" . $request->model;
        $method = $request->method_name;
        $params = $request->params ?? [];
        $instanceId = $request->instance_id;
        $type = $request->type;

        try {
            if ($type === 'static') {
                $result = $model::$method(...\array_values($params));
            } else {
                if (!$instanceId) {
                    return $this->corsResponse(['error' => 'à¸•à¹‰à¸­à¸‡à¸à¸²à¸£à¸„à¹ˆà¸² Instance ID (PK) à¹€à¸žà¸·à¹ˆà¸­à¹ƒà¸Šà¹‰à¸”à¸¶à¸‡à¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¸à¹‰à¸­à¸™à¸™à¸±à¹‰à¸™à¸¡à¸²à¸à¹ˆà¸­à¸™à¹€à¸£à¸µà¸¢à¸à¸Ÿà¸±à¸‡à¸à¹Œà¸Šà¸±à¸™'], 400);
                }
                $instance = $model::find($instanceId);
                if (!$instance) {
                    return $this->corsResponse(['error' => 'à¹„à¸¡à¹ˆà¸žà¸šà¸‚à¹‰à¸­à¸¡à¸¹à¸¥à¸‚à¸­à¸‡ ID: ' . $instanceId], 404);
                }
                $result = $instance->$method(...\array_values($params));
                
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
