<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return "Prueba usercontroller";
    }

    public function register(Request $request){

        //Rec\er los datos por POST

        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
            
        if(!empty($params) && !empty($params_array)){

            //Limpiar datos

            $params_array = array_map('trim',$params_array);

            //Validacion

            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', //Comprobar si existe el usuario
                'password'  => 'required'
            ]);

            if($validate->fails()){

                $data = array(
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => 'El usuario no se ha creado correctamente',
                    'errors'    => $validate->errors()
                );

            }else{

                //Cifrar contraseña 
          
                $pwd = hash('sha256', $params->password);
                

                //Creacion

                $user = new User();
                $user->name     = $params_array['name'];
                $user->surname  = $params_array['surname'];
                $user->role     = 'ROLE_USER';
                $user->email    = $params_array['email'];
                $user->password = $pwd;

                $user->save();

                $data = array(
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'El usuario se ha creado correctamente',
                    'user'      => $user
                );
            }

        }else{

            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos ingresados no son correctos'
            );

        }
        
        return response()->json($data, $data['code']);
    }

    public function login(Request $request){
        
        $jwtAuth = new \JwtAuth();

        // Recibe datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        if($validate->fails()){

            $signup = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'El usuario no se ha podido identificar',
                'errors'    => $validate->errors()
            );

        }else{

            $pwd = hash('sha256', $params->password);

            $signup = $jwtAuth->signup($params->email, $pwd);
            
            if(!empty($params->gettoken)){
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        
        return response()->json($signup, 200);

    }


    public function update(Request $request){
        
        //Comprueba si el usuario está identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        if($checkToken){
            
            //Recoge los datos
            $json = $request->input('json', null);
            $params_array = json_decode($json, true);

            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //Validacion
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users'.$user->sub
            ]);

            //Anular campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualiza el usuario en la BD
            $user_update = User::where('id', $user->sub)->update($params_array);

            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );

        }else{

            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );

        }

    return response()->json($data, $data['code']);

    }

    public function upload(Request $request){

        $image = $request->file('file0');
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        if(!$image || $validate->fails()){
            if(!$image){
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' =>  'Debes subir una imagen'
                );
            }else{
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' =>  'Error de tipo de archivo'
                );
            }
        }else{
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));
       
            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' =>  $image_name
            );
        
        }

        return response()->json($data, $data['code']);

    }


    public function getImage($filename)
    {

        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {

            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {

            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' =>  'La imagen no existe'
            );
        }
    }


    public function detail($id){

        $user = User::find($id);

        if (is_object($user)){

            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' =>  $user
            );

        }else{

            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' =>  'El usuario no existe'
            );

        }

        return response()->json($data, $data['code']);

    }
}