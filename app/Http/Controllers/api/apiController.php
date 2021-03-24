<?php

namespace App\Http\Controllers\api;

use App\Baby;
use App\Contact;
use App\Device;
use App\Http\Controllers\Controller;
use App\Mail\forgetPassword;
use App\Mail\sendRequest;
use App\Mail\verficationEmail;
use App\Status;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use mysql_xdevapi\Exception;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;




class apiController extends Controller
{
    function store(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'name' => 'required'

        ]);

        if ($validator->passes()) {

            $count = Device::where('mac', '=', $request->mac)->count();

            if ($count > 0) {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['This device is already being used by another account !']
                    ),
                    'data' => null

                );
                return response()->json($response, 200);
            }

            $digits = 6;
            $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'contact' => $request->contact,
                'api_token' => Str::random(60),
                'code' => $code,
                'isAdmin' => 0,
                'country' => $request->country,
                'gateway' => $request->gateway

            ]);

            Mail::to($request->email)->send(new verficationEmail($user, $code));

            $this->sendSms($request->contact, $code);

            if(isset($request->gateway) && !empty($request->gateway)){

                $this->sendEmailTosSms(['contact' => $request->contact, 'gateway' => $request->gateway, 'body' => "Your verification is : " . $code]);
            }



            if ($user) {

                try {
                    Device::create([
                        'email' => $request->email,
                        'mac' => $request->mac,
                        'user_id' => $user->id
                    ]);

                } catch (Throwable $e) {

                }


                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'User register successfully !'

                    ),
                    'data' => $user
                );

                return response()->json($response, 200);
            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['something went wrong ! ']

                    ),
                    'data' => null

                );

                return response()->json($response, 200);
            }


        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 200);

        }


    }

    function login(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'email' => 'required',
            'password' => 'required',

        ]);
        if ($validator->passes()) {

            $user = DB::table('users')->where('email', '=', $request->email)->first();


            if (isset($user->id)) {

                $val = Hash::check($request->password, $user->password);
            } else {

                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['The email or password is incorrect!']

                    ),
                    'data' => null

                );

                return response()->json($response, 200);
            }

            if ($val == 1) {

                if ($user->active == 0) {
                    $response = array(
                        'meta' => array(
                            'errCode' => 400,
                            'message' => ['Sorry you are not active user!']

                        ),
                        'data' => false

                    );

                    return response()->json($response, 200);
                }


                $devices = Device::where('email', $request->email)->get();

                $data = array(
                    'id' => $user->id,
                    'api_token' => $user->api_token,
                    'email' => $user->email,
                    'name' => $user->name,
                    'devices' => $devices

                );

                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'Login successfully !'

                    ),
                    'data' => $data

                );

                return response()->json($response, 200);

            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['The email or password is incorrect !']

                    ),
                    'data' => null

                );

                return response()->json($response, 200);
            }

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 200);

        }


    }


    function forgetPassword(Request $request)
    {


        $user = DB::table('users')->where('email', '=', $request->email)->first();

        if (isset($user->id)) {


            $digits = 4;
            $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

            Mail::to($request->email)->send(new forgetPassword($code));
            $this->sendSms($user->contact, $code);
            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Password reset code sent to e-mail and through sms !'
                ),
                'data' => array(
                    'code' => $code,
                    'api_token' => $user->api_token
                )


            );

            return response()->json($response, 200);


        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['Record not found ! ']

                ),
                'data' => null

            );

            return response()->json($response, 200);

        }
    }


    function forgetPasswords(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->passes()) {
            $user = DB::table('users')->where('email', '=', $request->email)->first();

            if ($user) {

                //   $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
                //   $code = substr($random, 0, 15);

                $digits = 4;
                $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

                Mail::to($request->email)->send(new forgetPassword($code));
                $this->sendSms($user->contact, $code);


                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'Password reset code sent to e-mail !'
                    ),
                    'data' => array(
                        'code' => $code,
                        'api_token' => $user->api_token
                    )


                );

                return response()->json($response, 200);


            } else {

                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['Record not found ! ']

                    ),
                    'data' => null

                );

                return response()->json($response, 200);

            }

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 200);


        }


    }

    function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'api_token' => 'required'
        ]);


        if ($validator->passes()) {

            $user = DB::table('users')->where('api_token', '=', $request->api_token)->first();


            if ($user) {

                if (!empty($request->oldPassword)) {

                    $val = Hash::check($request->oldPassword, $user->password);

                    if ($val == 0) {
                        $response = array(
                            'meta' => array(
                                'errCode' => 400,
                                'message' => ['The old password is incorrect !']
                            ),
                            'data' => true

                        );
                        return response()->json($response, 400);
                    } else {

                        $password = Hash::make($request->password);
                        $check = DB::table('users')->where('id', $user->id)->update(array('password' => $password));

                        $response = array(
                            'meta' => array(
                                'errCode' => 200,
                                'message' => 'Password has been successfully reset !'
                            ),
                            'data' => true

                        );
                        return response()->json($response, 200);
                    }


                }

                $password = Hash::make($request->password);
                $check = DB::table('users')->where('id', $user->id)->update(array('password' => $password));

                if ($check) {
                    $response = array(
                        'meta' => array(
                            'errCode' => 200,
                            'message' => 'Password has been successfully reset !'
                        ),
                        'data' => true

                    );
                    return response()->json($response, 200);


                } else {
                    $response = array(
                        'meta' => array(
                            'errCode' => 400,
                            'message' => ['something went wrong !']
                        ),
                        'data' => true

                    );
                    return response()->json($response, 200);

                }


            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['Record not found! ']

                    ),
                    'data' => null

                );

                return response()->json($response, 200);

            }


        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 200);
        }


    }

    function verifyUser(Request $request)
    {

        $check = User::where([['code', $request->code], ['id', $request->id]])->count();

        if ($check == 1) {

            $update = User::where('id', $request->id)->update(['active' => 1]);
            $user = User::where('id', $request->id)->first();


            if ($update == 1) {
                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'User verified !'
                    ),
                    'data' => $user

                );
                return response()->json($response, 200);

            }
        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['Verification code is incorrect!']
                ),
                'data' => true

            );
            return response()->json($response, 200);


        }
    }


    function resendCode(Request $request)
    {

        $user = User::where('id', $request->id)->first();

        if (isset($user->id)) {
            $digits = 6;
            $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
            $update = User::where('id', $request->id)->update(['code' => $code]);

            Mail::to($user->email)->send(new verficationEmail($user, $code));
            $this->sendSms($user->contact, $code);

            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Code resent successfully !'
                ),
                'data' => true

            );
            return response()->json($response, 200);
        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['No user found!']
                ),
                'data' => true

            );
            return response()->json($response, 200);
        }


    }


    function addEmergencyContact(Request $request)
    {


        $validator = Validator::make($request->all(), [

            'email' => 'required|email|unique:contacts,email',
//            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'required'
        ]);

        if ($validator->passes()) {

            $data = array(
                'name' => $request->name,
                'email' => $request->email,
                'contact' => $request->contact,
                'user_id' => $request->user_id,
                'country' => $request->country,
                'gateway' => $request->gateway
            );


            $check = Contact::where('user_id', $request->user_id)->count();

            if ($check >= 3) {

                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['You can add only three emergency contacts !']
                    ),
                    'data' => true

                );
                return response()->json($response, 200);

            } else {
                $contact = Contact::create($data);

                if (isset($contact->id)) {

                    $response = array(
                        'meta' => array(
                            'errCode' => 200,
                            'message' => 'Contact added successfully !'
                        ),
                        'data' => true

                    );
                    return response()->json($response, 200);

                } else {
                    $response = array(
                        'meta' => array(
                            'errCode' => 400,
                            'message' => 'Something went wrong !'
                        ),
                        'data' => true

                    );
                    return response()->json($response, 200);

                }


            }

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => false

            );

            return response()->json($response, 200);

        }

    }

    function updateEmergencyContact(Request $request)
    {


        $data = array(
            'name' => $request->name,
            'email' => $request->email,
            'contact' => $request->contact,
            'country' => $request->country,
            'gateway' => $request->gateway

        );

        $contact = Contact::where('id', $request->id)->update($data);

        if (isset($contact) && $contact == 1) {
            $contact = Contact::where('id', $request->id)->first();

            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Contact updated successfully !'
                ),
                'data' => $contact

            );
            return response()->json($response, 200);

        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => 'Something went wrong !'
                ),
                'data' => true

            );
            return response()->json($response, 200);

        }


    }


    function sendRequest(request $request)
    {

        $digits = 6;
        $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        $contact = Contact::where('id', $request->id)->first();

        if (isset($contact->id)) {

            $user = User::where('id', $contact->user_id)->first();
            try {
                Mail::to($contact->email)->send(new sendRequest($contact, $code, $user));

            } catch (\Exception $e) {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['Something went wrong !']
                    ),
                    'data' => true

                );
                return response()->json($response, 200);
            }
            db::table('contacts')->where('id', $contact->id)->update(['pin' => $code, 'verified' => true]);

            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Request Sent !'
                ),
                'data' => true

            );
            return response()->json($response, 200);

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['User not found !']
                ),
                'data' => true

            );
            return response()->json($response, 200);

        }
    }


    function addChildData(request $request)
    {

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->passes()) {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $imageName = time() . '.' . $extension;
                $file->move(public_path('/img'), $imageName);
                $image = '/img/' . $imageName;
            } else {
                $image = '/img/default.png';
            }


            $data = array(
                'name' => $request->name,
                'age' => $request->age,
                'userId' => $request->userId,
                'device' => $request->device,
                'macAddress' => $request->macAddress,
                'image' => $image,
                'identifier' => $request->identifier
            );
            $baby = Baby::create($data);


            if (isset($baby->id) && !empty($baby->id)) {

                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'Baby data added successfully !'
                    ),
                    'data' => $baby

                );
                return response()->json($response, 200);

            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['Something went wrong !']
                    ),
                    'data' => null

                );
                return response()->json($response, 200);
            }

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => false

            );

            return response()->json($response, 200);

        }


    }

    function updateChildData(request $request)
    {

        $validator = Validator::make($request->all(), [
            'image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->passes()) {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $imageName = time() . '.' . $extension;
                $file->move(public_path('/img'), $imageName);
                $image = '/img/' . $imageName;

                $data = array(
                    'name' => $request->name,
                    'age' => $request->age,
                    'device' => $request->device,
                    'macAddress' => $request->macAddress,
                    'image' => $image,
                    'identifier' => $request->identifier
                );
                $update = Baby::where('id', $request->id)->update($data);
            } else {

                $image = '/img/default.png';

                $data = array(
                    'name' => $request->name,
                    'age' => $request->age,
                    'device' => $request->device,
                    'macAddress' => $request->macAddress,
                    'identifier' => $request->identifier,
                    'image' => $image,
                );
                $update = Baby::where('id', $request->id)->update($data);
            }


            if (isset($update) && $update == 1) {

                $baby = Baby::where('id', $request->id)->first();

                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'Baby data updated successfully !'
                    ),
                    'data' => $baby

                );
                return response()->json($response, 200);

            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => ['Something went wrong !']
                    ),
                    'data' => null

                );
                return response()->json($response, 200);
            }

        } else {

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => false

            );

            return response()->json($response, 200);

        }


    }


    function getChildData($id)
    {

        $baby = Baby::where('userId', $id)->get();
        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Baby data !'
            ),
            'data' => $baby

        );
        return response()->json($response, 200);
    }

    function getContacts($id)
    {
        $baby = Contact::where('user_id', $id)->get();
        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Contact data !'
            ),
            'data' => $baby

        );
        return response()->json($response, 200);
    }


    function guestLogin(request $request)
    {

        $contact = Contact::where([['email', $request->email], ['pin', $request->pin]])->first();

        if (isset($contact->id)) {
            $user = User::where('id', $contact->user_id)->first();
            $check = db::table('tokens')->where('token', $request->app_token)->count();

            if ($check == 0) {
                db::table('tokens')->insert(
                    [
                        'user_id' => $contact->id,
                        'token' => $request->app_token
                    ]
                );
            }

            $data = array(

                'name' => $contact->name,
                'email' => $contact->email,
                'contact' => $contact->contact,
                'user_id' => $contact->user_id,
                'api_token' => $user->api_token,
                'contact_id' => $contact->id
            );

            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Login data !'
                ),
                'data' => $data

            );
            return response()->json($response, 200);

        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['Incorrect email or pin!']
                ),
                'data' => $contact

            );
            return response()->json($response, 200);
        }

    }

    function updateProfileStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'macAddress' => 'required',
        ]);

        if ($validator->passes()) {
            $date = Carbon::createFromTimestamp($request->time)->format('m/d/Y');


            $sid = 'AC942606c1aaf057cf6af68c83f7724376';
            $token = 'c372e6169b8e956bafb6f88e0b973c0b';

            try {
                $client = new Client($sid, $token);
            } catch (ConfigurationException $e) {
            }

            $loaction = '';

            if (isset($request->location)) {
                $loc = explode(',', $request->location);
                $loaction = 'https://www.google.com/maps/?q=' . $loc[0] . ',' . $loc[1];

            }


            $check = db::table('statuses')->where([['user_id', $request->user_id], ['mac', $request->macAddress]])->count();
            $user = User::where('id', $request->user_id)->first();
            $baby = Baby::where('id', $request->baby_id)->first();


            //update status
            if ($check == 1) {

                $s = db::table('statuses')->where([['user_id', $request->user_id], ['mac', $request->macAddress]])->first();

                if (isset($baby->name)) {
                    $childName = $baby->name;
                } else {
                    $childName = '';
                }
                $data = array(
                    'status' => $request->status,
                    'time' => $request->time,
                    'temp' => $request->temp,
                    'location' => $request->location,
                    'battery' => $request->battery,
                    'user_id' => $request->user_id,
                    'baby_id' => $request->baby_id,
                    'username' => $user->name,
                    'tel' => $user->contact,
                    'device' => $request->device,
                    'mac' => $request->macAddress,
                    'childName' => $childName,
                    'updated_at' => DB::raw('NOW()')
                );

                $update = db::table('statuses')->where('id', $s->id)->update($data);
                $status = db::table('statuses')->where('id', $s->id)->first();
                $contacts = db::table('contacts')->where('user_id', $request->user_id)->get();

                foreach ($contacts as $c) {

                    $tokens = db::table('tokens')->where('user_id', $c->id)->get();

                    foreach ($tokens as $token) {
                        if (isset($baby->name)) {

                            if ($request->status == 'Arrival') {
                                $this->sendpushnotification($token->token, 'Arrived', $baby->name . '\'s ' . $request->device . ' Deactivated');

                            } elseif ($request->status == 'Departure') {
                                $this->sendpushnotification($token->token, 'Departing', $baby->name . '\'s ' . $request->device . ' Activated');

                            } else {
                                $this->sendpushnotification($token->token, $request->status, 'The device status has changed for "' . $baby->name . '"');

                            }
                        } else {
                            $this->sendpushnotification($token->token, $request->status, 'The device status has changed for "' . $request->device . '"');
                        }
                    }
                    $current_time = Carbon::now()->timestamp;

                    if (isset($baby->name)) {
                        $notification = array(
                            'status' => $request->status,
                            'user_id' => $request->user_id,
                            'baby_id' => $request->baby_id,
                            'contact_id' => $c->id,
                            'name' => $baby->name,
                            'notification_timestamp' => $current_time,
                            'battery' => $request->battery,
                            'temprature' => $request->temp,
                            'location' => $loaction
                        );
                        db::table('notifications')->insert($notification);

                    } else {

                        $notification = array(
                            'status' => $request->status,
                            'user_id' => $request->user_id,
                            'baby_id' => $request->baby_id,
                            'contact_id' => $c->id,
                            'name' => $request->device,
                            'notification_timestamp' => $current_time,
                            'battery' => $request->battery,
                            'temprature' => $request->temp,
                            'location' => $loaction
                        );
                        db::table('notifications')->insert($notification);

                    }

                }


                if ($request->status == 'Child Unattended On') {

                    $contacts = db::table('contacts')->where('user_id', $request->user_id)->get();

                    if (!empty($baby)) {

                        foreach ($contacts as $c) {

                            if ($c->verified == 1) {
                                try {
                                    $client->messages->create(

                                        str_replace(' ', '', $c->contact),
                                        [

                                            'from' => '+12819151228',
                                            'body' => 'Urgent! ' . $childName . " is Unattended in the car Location : " . $loaction . " Driver :" . $user->name,
                                            'channel' => 'info'
                                        ]
                                    );
                                } catch (TwilioException $e) {
                                }
                            }


                        }

                    } else {
                        foreach ($contacts as $c) {
                            if ($c->verified == 1) {
                                try {

                                    $client->messages->create(

                                        str_replace(' ', '', $c->contact),
                                        [

                                            'from' => '+12819151228',
                                            'body' => "Child with device " . $request->device . " is unattended while in drive with " . $user->name
                                        ]
                                    );
                                } catch (TwilioException $e) {
//                             return response()->json($e->getMessage());
                                }
                            }

                        }

                    }

                }

                if ($update = 1) {

                    $response = array(
                        'meta' => array(
                            'errCode' => 200,
                            'message' => 'Status updated successfully !'
                        ),
                        'data' => $status

                    );
                    return response()->json($response, 200);

                } else {

                    $response = array(
                        'meta' => array(
                            'errCode' => 400,
                            'message' => 'Something went wrong!'
                        ),
                        'data' => []

                    );
                    return response()->json($response, 200);

                }

            } else {
                if (isset($baby->name) && !empty($baby->name)) {
                    $childName = $baby->name;
                } else {
                    $childName = '';
                }

                $data = array(
                    'status' => $request->status,
                    'time' => $request->time,
                    'temp' => $request->temp,
                    'location' => $request->location,
                    'battery' => $request->battery,
                    'user_id' => $request->user_id,
                    'baby_id' => $request->baby_id,
                    'username' => $user->name,
                    'tel' => $user->contact,
                    'device' => $request->device,
                    'mac' => $request->macAddress,
                    'childName' => $childName,
                    'updated_at' => DB::raw('NOW()')
                );

                $create = db::table('statuses')->insertGetId($data);
                $status = db::table('statuses')->where('id', $create)->first();

                //send push  notifications
                $contacts = db::table('contacts')->where('user_id', $request->user_id)->get();


                foreach ($contacts as $c) {

                    $tokens = db::table('tokens')->where('user_id', $c->id)->get();

                    foreach ($tokens as $token) {
                        if (isset($baby->name)) {

                            if ($request->status == 'Arrival') {
                                $this->sendpushnotification($token->token, 'Arrived', $baby->name . '\'s ' . $request->device . ' Deactivated');

                            } elseif ($request->status == 'Departure') {
                                $this->sendpushnotification($token->token, 'Departing', $baby->name . '\'s ' . $request->device . ' Activated');

                            } else {
                                $this->sendpushnotification($token->token, $request->status, 'The device status has changed for "' . $baby->name . '"');

                            }

                        } else {
                            $this->sendpushnotification($token->token, $request->status, 'The device status has changed for "' . $request->device . '"');
                        }
                    }
                    $current_time = Carbon::now()->timestamp;

                    if (isset($baby->name)) {
                        $notification = array(
                            'status' => $request->status,
                            'user_id' => $request->user_id,
                            'baby_id' => $request->baby_id,
                            'contact_id' => $c->id,
                            'name' => $baby->name,
                            'notification_timestamp' => $current_time,
                            'battery' => $request->battery,
                            'temprature' => $request->temp,
                            'location' => $loaction
                        );
                        db::table('notifications')->insert($notification);


                    } else {

                        $notification = array(
                            'status' => $request->status,
                            'user_id' => $request->user_id,
                            'baby_id' => $request->baby_id,
                            'contact_id' => $c->id,
                            'name' => $request->device,
                            'notification_timestamp' => $current_time,
                            'battery' => $request->battery,
                            'temprature' => $request->temp,
                            'location' => $loaction
                        );
                        db::table('notifications')->insert($notification);

                    }

                }


                if (isset($create)) {

                    if ($request->status == 'Child Unattended On') {

                        $contacts = db::table('contacts')->where('user_id', $request->user_id)->get();

                        if (!empty($baby)) {
                            foreach ($contacts as $c) {

                                if ($c->verified == 1) {
                                    try {

                                        $client->messages->create(

                                            str_replace(' ', '', $c->contact),
                                            [

                                                'from' => '+12819151228',
                                                'body' => "Urgent! \n " . $childName . " is Unattended in the car \n Location : " . $loaction . " \n Driver :" . $user->name,
                                            ]
                                        );
                                    } catch (TwilioException $e) {
//                             return response()->json($e->getMessage());
                                    }
                                }

                            }

                        } else {
                            foreach ($contacts as $c) {
                                if ($c->verified == 1) {
                                    try {

                                        $client->messages->create(

                                            str_replace(' ', '', $c->contact),
                                            [

                                                'from' => '+12819151228',
                                                'body' => "Child with device " . $request->device . " is unattended while in drive with " . $user->name
                                            ]
                                        );
                                    } catch (TwilioException $e) {
//                             return response()->json($e->getMessage());
                                    }
                                }

                            }

                        }

                    }


                    $response = array(
                        'meta' => array(
                            'errCode' => 200,
                            'message' => 'Status updated successfully !'
                        ),
                        'data' => $status

                    );
                    return response()->json($response, 200);

                } else {

                    $response = array(
                        'meta' => array(
                            'errCode' => 400,
                            'message' => 'Something went wrong!'
                        ),
                        'data' => []

                    );
                    return response()->json($response, 200);

                }

            }
        }else{

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => false

            );

            return response()->json($response, 200);

        }


    }


    public function sendpushnotification($token, $title, $body)
    {
        if (!empty($token)) {

            try {
                $optionBuilder = new OptionsBuilder();
                $optionBuilder->setTimeToLive(60 * 20);

                $notificationBuilder = new PayloadNotificationBuilder($title);
                $notificationBuilder->setBody($body)
                    ->setSound('default');

                $dataBuilder = new PayloadDataBuilder();
                $dataBuilder->addData(['a_data' => 'my_data']);

                $option = $optionBuilder->build();
                $notification = $notificationBuilder->build();
                $data = $dataBuilder->build();

                $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);


            } catch (Exception $e) {

//            return response()->json($e->getMessage());
            }
        }
    }

    function getProfileStatus($id)
    {

        $status = db::table('statuses');
        $status->where('user_id', '=', $id);
        $status->where('status', '!=', 'offline');
        $data = $status->get();

        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Status data !'
            ),
            'data' => $data

        );
        return response()->json($response, 200);


    }

    function logout(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'token' => 'required'
        ]);

        db::table('tokens')->where([['user_id', $request->id], ['token', $request->token]])->delete();
        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Success'
            ),
            'data' => false

        );
        return response()->json($response, 200);

    }

    function getNotifications($id)
    {
        $current_date_time = Carbon::now()->toDateTimeString();
        $date = explode(" ", $current_date_time);
        $time = strtotime($date[0]);
        $final = date("Y-m-d", strtotime("+1 month", $time));
        $notifications = db::table('notifications')->where('contact_id', $id)->whereBetween('created_at', [$time, $final])->orderBy('id', 'DESC')->get();
        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Notifications'
            ),
            'data' => $notifications
        );
        return response()->json($response, 200);
    }

    function sendLocation(request $request)
    {


        $contacts = db::table('contacts')->where([['user_id', $request->user_id], ['verified', 1]])->get();
        $baby = db::table('users')->where('id', $request->user_id)->first();

        $location = "https://www.google.com/maps/?q=" . $request->location;


        foreach ($contacts as $c) {
            $current_time = Carbon::now()->timestamp;

            $tokens = db::table('tokens')->where('user_id', $c->id)->get();

            foreach ($tokens as $token) {
                $this->sendpushnotification($token->token, 'Location', 'Map Link :' . $location);

            }

            $notification = array(
                'user_id' => $request->user_id,
                'baby_id' => 0,
                'contact_id' => $c->id,
                'name' => $baby->name,
                'notification_timestamp' => $current_time,
                'location' => $location,
                'type' => 'location'
            );
            db::table('notifications')->insert($notification);


//            try {
//                $client->messages->create(
//
//                    str_replace(' ', '',$c->contact),
//                    [
//
//                        'from' => '+12819151228',
//                        'body' =>"Map Link : ".$location,
//                        'channel'=>'info'
//                    ]
//                );
//            }catch (TwilioException $e){
//            }

        }
        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'Success'
            ),
            'data' => false

        );
        return response()->json($response, 200);

    }

    function retrieveLocation(Request $request)
    {

        $status = db::table('statuses')->where('user_id', $request->user_id)->latest('updated_at')->limit(1)->get();
        if (isset($status[0])) {
            $data = array(
                'location' => $status[0]->location
            );
            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Success'
                ),
                'data' => $data

            );
            return response()->json($response, 200);
        } else {
            $data = array();
            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Success'
                ),
                'data' => $data

            );
            return response()->json($response, 200);
        }

    }


    function updateUser(request $request)
    {


        $digits = 6;
        $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);


        $update = User::where('id', $request->id)->update(['code' => 1]);

        if (isset($update) && $update == 1) {

            $this->sendSms($request->contact, $code);


            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Verification code sent !'
                ),
                'data' => $code

            );
            return response()->json($response, 200);

        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => 'Something went wrong !'
                ),
                'data' => true

            );
            return response()->json($response, 200);

        }


    }


    function sendSms($contact, $code)
    {

        $sid = 'AC942606c1aaf057cf6af68c83f7724376';
        $token = 'c372e6169b8e956bafb6f88e0b973c0b';
        try {
            $client = new Client($sid, $token);
        } catch (ConfigurationException $e) {
        }
        try {
            $client->messages->create(

                str_replace(' ', '', $contact),
                [

                    'from' => '+12819151228',
                    'body' => "Your verification is : " . $code,
                    'channel' => 'info'
                ]
            );
        } catch (TwilioException $e) {
            dd($e->getMessage());
        }
    }


    function getDevices(request $request)
    {


        try {

            $device = Device::where('email', $request->email)->get();

            $user = User::where([['email', $request->email], ['api_token', $request->api_token]])->first();


            $data = array(
                'id' => $user->id,
                'api_token' => $user->api_token,
                'email' => $user->email,
                'name' => $user->name,
                'devices' => $device

            );

            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => 'Something went wrong !'
                ),
                'data' => $data

            );
            return response()->json($response, 200);

        } catch (Throwable $e) {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['Something went wrong !']
                ),
                'data' => true

            );
            return response()->json($response, 200);
        }


    }


    function verifyUpdateProfile(request $request)
    {

        $check = User::where([['code', $request->code], ['email', $request->email]])->count();

        if ($check == 1) {

            $update = User::where('id', $request->id)->update([

                'name' => $request->name,
                'email' => $request->email,
                'contact' => $request->contact


            ]);
            $user = User::where('id', $request->id)->first();


            if ($update == 1) {
                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'User verified !'
                    ),
                    'data' => $user

                );
                return response()->json($response, 200);

            }
        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => ['Verification code is incorrect!']
                ),
                'data' => true

            );
            return response()->json($response, 200);


        }


    }


    function updateDeviceName(request $request)
    {

        try {

            $device = Device::where('id', $request->id)->update(
                [
                    'name' => $request->name
                ]
            );

            if ($device == 1) {
                $response = array(
                    'meta' => array(
                        'errCode' => 200,
                        'message' => 'Name Updated !'
                    ),
                    'data' => true

                );
                return response()->json($response, 200);
            } else {
                $response = array(
                    'meta' => array(
                        'errCode' => 400,
                        'message' => 'Something went wrong !'
                    ),
                    'data' => true

                );
                return response()->json($response, 200);
            }


        } catch (Throwable $e) {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => 'Something went wrong !'
                ),
                'data' => true

            );
            return response()->json($response, 200);

        }

    }

    function addDeviceName(request $request)
    {

        $device = Device::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'mac' => $request->mac
            ]
        );

        if (isset($device->id)) {
            $response = array(
                'meta' => array(
                    'errCode' => 200,
                    'message' => 'Mac added successfully !'
                ),
                'data' => true

            );
            return response()->json($response, 200);
        } else {
            $response = array(
                'meta' => array(
                    'errCode' => 400,
                    'message' => 'Something went wrong !'
                ),
                'data' => true

            );
            return response()->json($response, 200);

        }

    }



    function getGateways()
    {

        $countries = db::table('carrier_gateway')->groupBy('country')->get();

        $gateways = array();

        foreach ($countries as $c) {

            $gateWay = db::table('carrier_gateway')->where('country', $c->country)->get();

            $cou = array(
                'country' => $c->country,
                'gateWay' => $gateWay
            );

            array_push($gateways, $cou);
        }

        $response = array(
            'meta' => array(
                'errCode' => 200,
                'message' => 'gateWay Address !'
            ),
            'data' => $gateways

        );
        return response()->json($response, 200);

    }

    function sendEmailTosSms($data)
    {

        try {


            Mail::raw($data['body'],function ($message) use ($data) {
                $gateWay = db::table('carrier_gateway')->where('id', $data['gateway'])->first();

                $address = $data['contact'] . $gateWay->address;

                $message->to($address)
                    ->subject('Test email to sms');
            });
        } catch (\Exception $e) {

        }

    }
}
