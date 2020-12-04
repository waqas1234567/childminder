<?php

namespace App\Http\Controllers;

use App\Baby;
use App\Contact;
use App\Post;
use App\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Http\Request;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use ZipArchive;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users=User::all();
        return view('home',compact('users'));
    }

    public function welcome()
    {
        $posts = Post::all();
        return view('welcome', ['posts' => $posts]);
    }

    public function generateQrcode(){
        return view('qrCode');
    }

    public function downloadQrcode(request $request){

        $check=preg_match('/^(([0-9a-zA-F]{2}-){5}|([0-9a-zA-F]{2}:){5})[0-9a-zA-F]{2}$/', $request->mac);

        if($check==1){
            $qrCode = new QrCode($request->mac);
            $qrCode->setSize(250);
            $qrCode->setMargin(10);
            $qrCode->setEncoding('UTF-8');
            $qrCode->setWriterByName('png');
            $qrCode->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0));
            $qrCode->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0.4));
            $qrCode->setLogoSize(100, 100);
            $qrCode->setValidateResult(false);
            $qrCode->setRoundBlockSize(true);
            $qrCode->setLabel('Childminder');
            $qrCode->setLabelAlignment('center');
//        $qrCode->setLabelMargin(['b'=>0,'t'=>3]);
            $qrCode->setLogoPath(public_path('Group.png'));

            $qrCode->setLogoSize(100, 100);
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
            $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
            header('Content-Type: '.$qrCode->getContentType());

            $path=public_path('/qrCodes/'.time().'.png');

            $qrCode->writeFile($path);
            $file= $path;

            $headers = array(
                'Content-Type: application/png',
            );
//            Session::flash('success', 'Qrcode generated successfully!');

            return Response::download($file,time().'.png', $headers);
        }else{

            return redirect()->back()->with(['error'=>'Mac address is not valid']);
        }


    }

    public function contacts($id){

        $contacts=Contact::where('user_id',$id)->get();
        return view('contact',compact('contacts'));


    }


    public function babies($id){
         $babies= Baby::where('userId',$id)->get();
        return view('babies',compact('babies'));

    }

    public function destroy(Request $request){
          $delete= User::where('id',$request->id)->delete();

          if($delete==1){
              return response()->json(['status'=>200]);
          }else{
              return response()->json(['status'=>400]);

          }
    }


}
