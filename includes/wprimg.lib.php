<?php

if (!function_exists('imagecropi')) {
    // 对齐模式
    // LT | T | RT
    // L  |   |  R
    // LB | B | RB
    define('ALIGN_TOP_LEFT',     'TL');
    define('ALIGN_TOP',          'T');
    define('ALIGN_TOP_RIGHT',    'TR');
    define('ALIGN_LEFT',         'L');
    define('ALIGN_AUTO',         '');         // 上下, 左右居中
    define('ALIGN_RIGHT',        'R');
    define('ALIGN_BOTTOM_LEFT',  'BL');
    define('ALIGN_BOTTOM',       'B');
    define('ALIGN_BOTTOM_RIGHT', 'BR');

    define('SCALE_FILL',         'FILL');     // 等比例缩放, 填满
    define('SCALE_PLACE',        'PLACE');    // 等比例缩放, 放置到尺寸内, 上下或左右可能出现空白, 需要指定填充颜色
    define('SCALE_X',            'X');        // 以 x 轴缩放系数按比例缩放
    define('SCALE_Y',            'Y');        // 以 y 轴缩放系数按比例缩放
    define('SCALE_STRETCH',      'STRETCH');  // 拉伸填满尺寸, 不保持比例

    /**
     * 图片智能裁切
     * @param  [type]  $source_file 原始图片文件
     * @param  [type]  $size        裁切目标尺寸. w100|h100|100x100|[w,h,x,y]
     *                              array(
     *                                  // 输出图片尺寸
     *                                  width  => 100,
     *                                  height => 100,
     *                                  // 原始图片裁切位置
     *                                  x      => 50,
     *                                  y      => 50,
     *                                  // 原始图片裁切尺寸
     *                                  w      => 300,
     *                                  h      => 300,
     *                              )
     * @param  string  $align_mode  对齐模式.
     * @param  string  $scale       缩放模式
     * @return [type]               [description]
     */
    function imagecropi($source_file, $size, $align_mode = '', $scale_mode = 'FILL', $bgcolor = 0xFFFFFF, $target_file = FALSE)
    {
        $quality = 85;
        if (!file_exists($source_file)) {
            return 1;
        }
        $img_r = imagefromfile($source_file);
        if (!$img_r) return 2;

        $output_file = $source_file;

        $img_size = getimagesize($source_file);
        if (!$size) {
            $size = array(
                'width'  => $img_size[0],
                'height' => $img_size[1],
            );
        }
        $img_w = $img_size[0];
        $img_h = $img_size[1];
        if (is_array($size)) {
            $out_w  = $size['width'];
            $out_h  = $size['height'];
            $crop_x = isset($size['x']) ? $size['x'] : 0;
            $crop_y = isset($size['y']) ? $size['y'] : 0;
            $crop_w = !empty($size['w']) ? $size['w'] : ($img_w - $crop_x);
            $crop_h = !empty($size['h']) ? $size['h'] : ($img_h - $crop_h);

            if ($out_w == $out_h) {
                $size = $out_w . '';
            }else{
                $size = $out_w . 'x' . $out_h;
            }
        }else{
            if (preg_match('/(\d+x\d+|w\d+|h\d+|\d+)/', $size) != 1) {
                return 3;
            }

            $crop_x = -1;
            $crop_y = -1;
            $crop_w = $img_w;
            $crop_h = $img_h;

            $_flag = substr($size, 0, 1);
            if ($_flag == 'w') {
                $out_w = substr($size, 1) * 1;
                $out_h = ($out_w / $crop_w) * $crop_h;
            }else if ($_flag == 'h') {
                $out_h = substr($size, 1) * 1;
                $out_w = ($out_h / $crop_h) * $crop_w;
            }else if (strpos($size, 'x')){
                $_s = explode('x', $size);
                $out_w = $_s[0] * 1;
                $out_h = $_s[1] * 1;
            }else{
                $out_w = $size * 1;
                $out_h = $size * 1;
            }

        }

        $scale_x = $out_w / $crop_w;
        $scale_y = $out_h / $crop_h;
        switch($scale_mode){
            case SCALE_PLACE:
                $min = $scale_x > $scale_y ? $scale_y : $scale_x;
                $scale_x = $scale_y = $min;
                break;
            case SCALE_X:
                $scale_y = $scale_x;
                break;
            case SCALE_Y:
                $scale_x = $scale_y;
                break;
            case SCALE_STRETCH:
                break;
            case SCALE_FILL:
            default:
                $max = $scale_x < $scale_y ? $scale_y : $scale_x;
                $scale_x = $scale_y = $max;
        }

        // 根据缩放模式调整裁切位置及尺寸
        $crop_wn = $out_w / $scale_x;
        $crop_hn = $out_h / $scale_y;

        if (strpos($align_mode, 'L') !== FALSE) {
            if ($crop_x == -1) {
                $crop_x = 0;
            }
        }else if (strpos($align_mode, 'R') !== FALSE) {
            if ($crop_x == -1){
                $crop_x = ($img_w - $crop_wn);
            }else{
                $crop_x = $crop_x + ($crop_wn - $crop_w);
            }
        }else{
            if ($crop_x == -1){
                $crop_x = ($img_w - $crop_wn) / 2;
            }else{
                $crop_x = $crop_x + ($crop_wn - $crop_w) / 2;
            }
        }
        $crop_x = $crop_x < 0 ? 0 : floor($crop_x);

        if (strpos($align_mode, 'T') !== FALSE) {
            if ($crop_y == -1)
                $crop_y = ($img_h - $crop_h) / 2;
        }else if (strpos($align_mode, 'B') !== FALSE) {
            if ($crop_y == -1)
                $crop_y = ($img_h - $crop_h);
            $crop_y = $crop_y - ($crop_hn - $crop_h);
        }else{
            if ($crop_y == -1)
                $crop_y = 0;
            $crop_y = $crop_y - ($crop_hn - $crop_h) / 2;
        }
        $crop_y = $crop_y < 0 ? 0 : floor($crop_y);

        $crop_w = floor($crop_wn);
        $crop_h = floor($crop_hn);

        $dst_r = ImageCreateTrueColor( $out_w, $out_h );
        $cr = $bgcolor >> 4;
        $cg = ($bgcolor << 2) >> 4;
        $cb = ($bgcolor << 4) >> 4;
        $cr = $cr > 255 ? 255 : $cr;
        $cg = $cg > 255 ? 255 : $cg;
        $cb = $cb > 255 ? 255 : $cb;
        $color = imagecolorallocate($dst_r, $cr, $cg, $cb);
        imagefill($dst_r, 0, 0, $color);

        imagecopyresampled(
            $dst_r,            $img_r,
            0, 0,              $crop_x, $crop_y,
            $out_w, $out_h,    $crop_w, $crop_h);

        if (!$target_file) {
            $target_file = preg_replace('/(\.\w+)$/', '-' . $size . '$1', $source_file);
        }
        $ret = imagetofile($dst_r, $target_file, $quality);

        return $ret ? $target_file : 4;
    }

    //
    // Function imagecropi end
    //
}

if (!function_exists('imagerotatei')) {

	/**
	 * 校正图片方向
	 * @param  [type]  $filename    [description]
	 * @param  [type]  $orientation [description]
	 * @param  integer $quality     [description]
	 * @return [type]               [description]
	 */
	function imagerotatei($image_source, $orientation = 0, $quality = 90, $target_file = FALSE)
	{
        $ret = TRUE;
        $target_file = $target_file ? $target_file : $image_source;
        $exif = exif_read_data($image_source);

        if (!empty($exif['Orientation'])) {
            $image = imagefromfile($image_source);
            switch($exif['Orientation']) {
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                default:
                    $image = FALSE;
            }

            if ($image) {
                $ret = imagetofile($image, $target_file, $quality);
            }
        }

        return $ret ? $target_file : 4;
    }

}

if (!function_exists('imagefromfile')) {

	function imagefromfile($filename)
	{
		if (preg_match('/\.(jpg|jpeg)$/i', $filename)) {
			$img_r = imagecreatefromjpeg($filename);
		}else if(preg_match('/\.png$/i', $filename)){
			$img_r = imagecreatefrompng($filename);
		}else if(preg_match('/\.gif$/i', $filename)){
			$img_r = imagecreatefromgif($filename);
		}else{
			$img_r = FALSE;
		}
		return $img_r;
	}

	function imagetofile($gdimage, $filename, $quality = 90)
	{
		if (!$gdimage) return FALSE;

		if (preg_match('/\.(jpeg|jpg)$/i', $filename)) {
			$ret = imagejpeg($gdimage, $filename, $quality);
		}else if(preg_match('/\.png$/i', $filename)){
			$ret = imagepng($gdimage, $filename, $quality / 10);
		}else if(preg_match('/\.gif$/i', $filename)){
			$ret = imagegif($gdimage, $filename);
		}else{
			$ret = FALSE;
		}
		return $ret;
	}

}

if (!function_exists('imagewatermark')) {
    function imagewatermark($source, $target, $watermark) {
        $imgInfo = getimagesize($source);
        $markInfo = getimagesize($watermark);

        $wm_x = ($imgInfo[0] - $markInfo[0]) / 2;
        $wm_y = $imgInfo[1] - 32 - $markInfo[1];

        $img = imagefromfile($source);
        $mark = imagefromfile($watermark);
        imagecopy($img, $mark,
            $wm_x, $wm_y,
            0, 0, $markInfo[0], $markInfo[1]
        );

        imagetofile($img, $target);
    }
}

//
// File END
//
//