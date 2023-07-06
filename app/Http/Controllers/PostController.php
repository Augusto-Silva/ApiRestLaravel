<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;


class PostController extends Controller
{

    public function __construct()
    {
        $this->middleware('api.auth', ['except' => [
            'index',
            'show',
            'getImage',
            'getPostsByCategory',
            'getPostsByUser'
        ]]);
    }


    public function index(Request $request){

        $posts = Post::all()->load('category');

        return response()->json([
            'code'          => 200,
            'status'        => 'success',
            'posts'         => $posts
        ], 200);

    }

    public function show($id){

        $post = Post::find($id)->load('category');

        if (is_object($post)) {
            
            $data = [
                'code'      =>  200,
                'status'    =>  'success',
                'posts'     =>  $post
            ];

        }else{

            $data = [
                'code'      =>  404,
                'status'    =>  'error',
                'message'   =>  'El post no existe'
            ];

        }

        return response()->json($data, $data['code']);

    }


    public function store(Request $request){

        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {

            $user = $this->getIdentity($request);

            $validate = \Validator::make($params_array, [
                'title'        => 'required',
                'content'      => 'required',
                'category_id'  => 'required',
                'image'        => 'required'
            ]);

            if ($validate->fails()) {
            
                $data = [
                    'code'      =>  400,
                    'status'    =>  'error',
                    'message'   =>  'No se ha guardado el post, faltan datos'
                ];
            
            }else{

                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();
                
                $data = [
                    'code'      =>  200,
                    'status'    =>  'success',
                    'post'   =>  $post
                ];

            }



        }else{

            $data = [
                'code'      =>  400,
                'status'    =>  'error',
                'message'   =>  'Envia los datos correctamente'
            ];

        }

        return response()->json($data, $data['code']);

    }


    public function update($id, Request $request)
    {
        
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){

            $validate = \Validator::make($params_array, [
                'title'        => 'required',
                'content'      => 'required',
                'category_id'  => 'required',
                'image'        => 'required'
            ]);

            if ($validate->fails()) {

                $data = [
                    'code'          => 400,
                    'status'        => 'error',
                    'message'       => 'El post no se ha guardado'
                ];

            } else {

                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['user']);
                unset($params_array['created_at']);

                $user = $this->getIdentity($request);

                $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

                if (!empty($post)) {

                    $post->update($params_array);

                    $data = array(
                        'code' => 200,
                        'status' => 'success',
                        'changes' => $params_array
                    );

                }else{

                    $data = array(
                        'code'      => 400,
                        'status'    => 'error',
                        'message'   => 'Debes ingresar un post admitido'
                    );

                }

            }
            
        }else{

                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'message'   => 'Debes ingresar un post'
                );

        }

        return response()->json($data, $data['code']);

    }


    public function destroy($id, Request $request){

        $user = $this->getIdentity($request);

        $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

        if (!empty($post)) {
              
            $post->delete();

            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'post'      => $post
            );

        }else{

            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El post no existe'
            );

        }

        return response()->json($data, $data['code']);

    }

    
    private function getIdentity($request)
    {

        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;

    }


    public function upload(Request $request){

        $image = $request->file('file0');

        $validate = \Validator::make($request->all(), [
            'file0' =>  'required|mimes:jpg,jpeg,png,gif'
        ]);

        if($validate->fails()){

            $data = [
                'code'      =>      400,
                'status'    =>      'error',
                'message'   =>      'Error al subir la imagen'
            ];

        }else{

            $image_name = time().$image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code'      =>      200,
                'status'    =>      'success',
                'image'     =>      $image_name
            ];

        }

        return response()->json($data, $data['code']);

    }


    public function getImage($filename){

        $isset = \Storage::disk('images')->exists($filename);

        if($isset){

            $file = \Storage::disk('images')->get($filename);

            return new Response($file, 200);

        }else{

            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La imagen no existe'
            ];

        }

        return response()->json($data, $data['code']);

    }


    public function getPostsByCategory($id){

        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status'    => 'success',
            'posts'     => $posts
        ], 200);

    }


    public function getPostsByUser($id){

        $posts = Post::where('user_id', $id)->get();

        return response()->json([
            'status'    => 'success',
            'posts'     => $posts
        ], 200);
        
    }

}