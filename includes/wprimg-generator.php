<?php
require_once('./wprimg.lib.php');

/**
 * Wordpress thumbnail generator
 */

function parse_file_info($file_path) {
    $info = array();
    $path_ary = explode('/', $file_path);
    $path_count = count($path_ary);
    $info['query'] = $file_path;
    $info['filename'] = $path_ary[ $path_count - 1 ];
    array_pop($path_ary);
    $info['filepath'] = implode('/', $path_ary);
    /**
     * -{尺寸}{偏移}{对齐}{裁切}
     */
    $pat = '/-([whxy]\d+)+(\w{1,3})?\.(jpg|jpeg|png|gif)$/i';
    if (preg_match($pat, $info['filename'])) {
        $info['is_thumb'] = TRUE;
        $info['original'] = preg_replace($pat, '.$3', $info['filename']);

        $conf = array();
        preg_match_all($pat, $info['filename'], $conf);
        // $info['config'] = $conf;
        $info['format'] = $conf[3][0];
        $conf_str = preg_replace('/^-|\.\w+$/', '', $conf[0][0]);
        $conf_str = strtoupper($conf_str);
        if (preg_match('/W\d+/', $conf_str)) {
            $info['t_width'] = preg_replace('/.*W(\d+).*/', '$1', $conf_str);
        }
        if (preg_match('/H\d+/', $conf_str)) {
            $info['t_height'] = preg_replace('/.*H(\d+).*/', '$1', $conf_str);
        }
        if (preg_match('/X\d+/', $conf_str)) {
            $info['t_x'] = preg_replace('/.*X(\d+).*/', '$1', $conf_str);
        }
        if (preg_match('/Y\d+/', $conf_str)) {
            $info['t_y'] = preg_replace('/.*Y(\d+).*/', '$1', $conf_str);
        }

        /**
         *  Align Mode
         *   TL  TT  TR
         *   LL  CM  RR
         *   BL  BB  BR
         */
        if (preg_match('/(TL|TT|TR|LL|CM|RR|LB|BB|LR)/', $conf_str)) {
            $info['t_align'] = preg_replace('/.*(TL|TT|TR|LL|CM|RR|LB|BB|LR).*/', '$1', $conf_str);
        } else {
            $info['t_align'] = 'CM';
        }

        /**
         * Crop mode
         * S  ->  Fix size setting, strengh image.
         * I  ->  Image contain in size setting, keep ratio
         * O  ->  Image fill in size and crop pixel out of rectangle
         */
        if (preg_match('/[SIO]/', $conf_str)) {
            $info['t_crop'] = preg_replace('/.*([SIO]).*/', '$1', $conf_str);
        } else {
            $info['t_crop'] = 'O';
        }

        $info['config_str'] = $conf_str;
    } else {
        $info['original'] = $info['filename'];
        // Do nothing
    }
    $info['input'] = $info['filepath'] . '/' . $info['original'];
    $info['output'] = $info['filepath'] . '/' . $info['filename'];

    return $info;
}


$image_info = parse_file_info($_SERVER['REQUEST_URI']);

if (!isset($image_info['t_width'])) {
    $size = 'h' . $image_info['t_height'];
} else if (!isset($image_info['t_height'])) {
    $size = 'w' . $image_info['t_width'];
} else {
    $size = $image_info['t_width'] . 'x' . $image_info['t_height'];
}

switch($image_info['t_align']) {
    case 'TT':
    case 'RR':
    case 'BB':
    case 'LL':
        $align = substr($image_info['t_align'], 0, 1);
        break;
    case 'CM':
        $align = '';
    default:
        $align = $image_info['t_align'];
}

switch($image_info['t_crop']) {
    case 'S':
        $crop = 'STRETCH';
        break;
    case 'I':
        $crop = 'PLACE';
        break;
    case 'O':
        $crop = 'FILL';
        break;
}

$base_path = realpath('../');
$source = $base_path . $image_info['input'];
$target = $base_path . $image_info['output'];

if (file_exists($source)) {
    echo '<p>source found at: '.$source.'</p>';
} else {
    echo '<p>source not exists</p>';
    exit(1);
}

$ret = imagecropi(
    $source,
    $size,
    $align,
    $crop,
    0xFFFFFF,
    $target,
);


if (file_exists($target)) {
    header("Location: " . $image_info['output']);
}