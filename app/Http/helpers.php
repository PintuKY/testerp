<?php

use App\Models\ApiSetting;
use Automattic\WooCommerce\Client;

/**
 * boots pos.
 */
function pos_boot($ul, $pt, $lc, $em, $un, $type = 1, $pid = null)
{
    $ch = curl_init();
    $request_url = ($type == 1) ? base64_decode(config('author.lic1')) : base64_decode(config('author.lic2'));

    $pid = is_null($pid) ? config('author.pid') : $pid;

    $curlConfig = [CURLOPT_URL => $request_url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => [
            'url' => $ul,
            'path' => $pt,
            'license_code' => $lc,
            'email' => $em,
            'username' => $un,
            'product_id' => $pid
        ]
    ];
    curl_setopt_array($ch, $curlConfig);
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = 'C' . 'U' . 'RL ' . 'E' . 'rro' . 'r: ';
        $error_msg .= curl_errno($ch);

        return redirect()->back()
            ->with('error', $error_msg);
    }
    curl_close($ch);

    if ($result) {
        $result = json_decode($result, true);

        if ($result['flag'] == 'valid') {
            // if(!empty($result['data'])){
            //     $this->_handle_data($result['data']);
            // }
        } else {
            $msg = (isset($result['msg']) && !empty($result['msg'])) ? $result['msg'] : "I" . "nvali" . "d " . "Lic" . "ense Det" . "ails";
            return redirect()->back()
                ->with('error', $msg);
        }
    }
}

if (!function_exists('humanFilesize')) {
    function humanFilesize($size, $precision = 2)
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;

        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }

        return round($size, $precision) . $units[$i];
    }
}

/**
 * Checks if the uploaded document is an image
 */
if (!function_exists('isFileImage')) {
    function isFileImage($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $array = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'];
        $output = in_array($ext, $array) ? true : false;
        return $output;
    }
}

function isAppInstalled()
{
    $envPath = base_path('.env');
    return file_exists($envPath);
}

/**
 * Checks if pusher has credential or not
 *
 * and return boolean
 */
function isPusherEnabled()
{
    $is_pusher_enabled = false;

    if (!empty(config('broadcasting.connections.pusher.key')) && !empty(config('broadcasting.connections.pusher.secret')) && !empty(config('broadcasting.connections.pusher.app_id')) && !empty(config('broadcasting.connections.pusher.options.cluster')) && (config('broadcasting.connections.pusher.driver') == 'pusher')) {
        $is_pusher_enabled = true;
    }

    return $is_pusher_enabled;
}

/**
 * Checks if user agent is mobile or not
 *
 * @return boolean
 */
if (!function_exists('isMobile')) {
    function isMobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('str_ordinal')) {
    /**
     * Append an ordinal indicator to a numeric value.
     *
     * @param string|int $value
     * @param bool $superscript
     * @return string
     */
    function str_ordinal($value, $superscript = false)
    {
        $number = abs($value);

        $indicators = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        $suffix = $superscript ? '<sup>' . $indicators[$number % 10] . '</sup>' : $indicators[$number % 10];
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            $suffix = $superscript ? '<sup>th</sup>' : 'th';
        }

        return number_format($number) . $suffix;
    }
}

// Variations types lists
function variationTypes()
{
    return [
        '1' => 'Select',
        '2' => 'Radio Button',
        '3' => 'input'
    ];
}

// Transaction actity type
function TransactionActivityTypes()
{
    $data = [
        'DaysUpdate' => '1',
        'UserComment' => '2',
        'Auto' => '3'
    ];
    return $data;
}

// Transaction actity type
function reasonCancelOrder()
{
    $data = [
        '1' => 'Food Not Cooked',
        '2' => 'Not Packaged',
        '3' => 'Wrong Delivery',
    ];
    return $data;
}

function getReasonName($day)
{

    $dayName = reasonCancelOrder();
    return isset($dayName[$day]) ? $dayName[$day] : "NA";
    // $dayName = '';
    // switch ($day) {
    //     case ('1'):
    //         $dayName = 'Food Not Cooked';
    //         break;
    //     case ('2'):
    //         $dayName = 'Not Packaged';
    //         break;
    //     case ('3'):
    //         $dayName = 'Wrong Delivery';
    //         break;
    //     default:
    //         $dayName = 'NA';
    // }
    // return $dayName;
}

// MEal types lists
function mealTypes()
{
    $data = [
        '4' => 'None',
        '1' => 'Lunch (9.30am ??? 1.30pm)',
        '2' => 'Dinner (3.30pm ??? 7.30pm)',
        '3' => 'Both'
    ];
    return $data;
}
function getMealTypes($type)
{
    $meal_type = 0;
    switch ($type) {
        case ('1'):
            $meal_type = 'Lunch (9.30am ??? 1.30pm)';
            break;
        case ('2'):
            $meal_type = 'Dinner (3.30pm ??? 7.30pm)';
            break;
        case ('3'):
            $meal_type = 'Both';
            break;
        case ('4'):
            $meal_type = 'None';
            break;
        default:
            $meal_type = '';
    }
    return $meal_type;
}
// compensate types lists
function compensateTypes()
{
    $data = [
        '0' => 'No',
        '1' => 'Yes',
    ];
    return $data;
}

// MEal types lists for compensate
function compensateMealTypes()
{
    $data = [
        '1' => 'Lunch (9.30am ??? 1.30pm)',
        '2' => 'Dinner (3.30pm ??? 7.30pm)',
    ];
    return $data;
}


// number of Days lists
function noOfDays()
{
    $data = [
        '7' => '7',
        '10' => '10',
        '20' => '20',
        '30' => '30'
    ];
    return $data;
}

// deliveryDays lists
function deliveryDays()
{
    $data = [
        '1' => 'Monday',
        '2' => 'Tuesday',
        '3' => 'Wednesday',
        '4' => 'Thursday',
        '5' => 'Friday',
        '6' => 'Saturday',
        '7' => 'Sunday',
    ];
    return $data;
}

// Driver type
function driverTypes()
{
    $data = [
        '1' => 'Full Time',
        '2' => 'Part Time'
    ];
    return $data;
}

//get driver type
function getDriverType($type)
{
    $driver_type = '';
    switch ($type) {
        case ('1'):
            $driver_type = 'Full Time';
            break;
        case ('2'):
            $driver_type = 'Part Time';
            break;
        default:
            $driver_type = 'NA';
    }
    return $driver_type;
}


// Leave reason type
function LeaveReasonTypes()
{
    $data = [
        '0' => 'none',
        '1' => 'Casual Leave',
        '2' => 'Annual leave',
        '3' => 'Childcare leave',
        '4' => 'Sick leave',
        '5' => 'Half Day Leave'
    ];
    return $data;
}

//get leave reason type
function getLeaveReasonType($type)
{
    $leave_reason = '';
    switch ($type) {
        case ('1'):
            $leave_reason = 'Casual Leave';
            break;
        case ('2'):
            $leave_reason = 'Annual leave';
            break;
        case ('3'):
            $leave_reason = 'Childcare leave';
            break;
        case ('4'):
            $leave_reason = 'Sick leave';
            break;
        case ('5'):
            $leave_reason = 'Half Day Leave';
            break;
        case ('0'):
            $leave_reason = 'NA';
            break;
        default:
            $leave_reason = 'NA';
    }
    return $leave_reason;
}

// City lists
function city()
{
    $data = [
        'singapore' => 'Singapore',
    ];
    return $data;
}


// State lists
function state()
{
    $data = [
        'singapore' => 'Singapore',
    ];
    return $data;
}

// Country lists
function country()
{
    $data = [
        'singapore' => 'Singapore',
    ];
    return $data;
}
function ingredientMeasure()
{
    $data = [
        'g' => 'gram',
        'kg' => 'kilogram',
        'lb' => 'pound',
        'mg' => 'milligram',
        'oz' => 'ounce',
        'doz' => 'dozen',
        'gal' => 'gallon',
        'mL' => 'milliliter',
        'L' => 'liter',
        'large' => 'large',
        'small' => 'small',
    ];
    return $data;
}

function getingredientMeasure($measure)
{
    $measure_type = 0;
    switch ($measure) {
        case ('small'):
            $measure_type = 'small';
            break;
        case ('large'):
            $measure_type = 'large';
            break;
        case ('L'):
            $measure_type = 'liter';
            break;
        case ('mL'):
            $measure_type = 'milliliter';
            break;
        case ('gal'):
            $measure_type = 'gallon';
            break;
        case ('doz'):
            $measure_type = 'dozen';
            break;
        case ('oz'):
            $measure_type = 'oz';
            break;
        case ('mg'):
            $measure_type = 'milligram';
            break;
        case ('lb'):
            $measure_type = 'pound';
            break;
        case ('kg'):
            $measure_type = 'kilogram';
            break;
        case ('g'):
            $measure_type = 'gram';
            break;
        default:
            $measure_type = '';
    }
    return $measure_type;
}
if (!function_exists('getDayNumberByDayName')) {

    function getDayNumberByDayName($day)
    {
        $dayNumber = 0;
        switch ($day) {
            case ('Mon'):
                $dayNumber = 1;
                break;
            case ('Monday'):
                $dayNumber = 1;
                break;
            case ('Tues'):
                $dayNumber = 2;
                break;
            case ('Tuesday'):
                $dayNumber = 2;
                break;
            case ('Wed'):
                $dayNumber = 3;
                break;
            case ('Wednesday'):
                $dayNumber = 3;
                break;
            case ('Thurs'):
                $dayNumber = 4;
                break;
            case ('Thursday'):
                $dayNumber = 4;
                break;
            case ('Fri'):
                $dayNumber = 5;
                break;
            case ('Friday'):
                $dayNumber = 5;
                break;
            case ('Sat'):
                $dayNumber = 6;
                break;
            case ('Saturday'):
                $dayNumber = 6;
                break;
            case ('Sun'):
                $dayNumber = 7;
                break;
            case ('Sunday'):
                $dayNumber = 7;
                break;
            default:
                $dayNumber = 0;
        }
        return $dayNumber;
    }


}
function getDayNameByDayNumber($day)
{
    $dayNumber = '';
    switch ($day) {
        case ('1'):
            $dayNumber = 'Monday';
            break;
        case ('2'):
            $dayNumber = 'Tuesday';
            break;
        case ('3'):
            $dayNumber = 'Wednesday';
            break;
        case ('4'):
            $dayNumber = 'Thursday';
            break;
        case ('5'):
            $dayNumber = 'Friday';
            break;
        case ('6'):
            $dayNumber = 'Saturday';
            break;
        case ('7'):
            $dayNumber = 'Sunday';
            break;
        default:
            $dayNumber = '0';
    }
    return $dayNumber;
}

// api configuration for getting data from api
if (!function_exists('getConfiguration')) {

    function getConfiguration($bussiness_location_id)
    {
        $apiSettings = ApiSetting::where('id', $bussiness_location_id)->first();

        return new Client(
            $apiSettings->url,
            $apiSettings->consumer_key,
            $apiSettings->consumer_secret
        );
    }
}

// for getting data from api
if (!function_exists('getData')) {
    function getData($configuration, $endPoint)
    {
        return $configuration->get($endPoint, $parameters = []);
    }
}

// for getting data from api
if (!function_exists('getApiSettingData')) {
    function getApiSettingData()
    {
        return ApiSetting::get();
    }
}
// for getting order status in number
if (!function_exists('getOrderStatusNumber')) {
    function getOrderStatusNumber($status)
    {
        switch ($status) {
            case ('refunded'):
                $status = '6';
                break;
            case ('completed'):
                $status = '3';
                break;
            case ('final'):
                $status = '1';
                break;
            case ('processing'):
                $status = '2';
                break;
            case ('cancelled'):
                $status = '4';
                break;
            case ('failed'):
                $status = '7';
                break;
            case ('payment_pending'):
                $status = '5';
                break;
            default:
                $status = '';
        }
        return $status;
    }
}




