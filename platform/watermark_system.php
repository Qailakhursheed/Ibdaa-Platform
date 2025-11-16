<?php
/**
 * نظام العلامة المائية للصور - منصة إبداع
 * يضيف شعار المنصة كعلامة مائية على جميع الصور
 */

class WatermarkManager {
    private $watermarkPath = __DIR__ . '/photos/Sh.jpg'; // شعار المنصة
    private $watermarkOpacity = 30; // الشفافية (0-100)
    private $watermarkPosition = 'bottom-right'; // center, top-left, top-right, bottom-left, bottom-right
    private $watermarkSize = 15; // حجم العلامة المائية (نسبة مئوية من الصورة)
    private $watermarkPadding = 20; // المسافة من الحواف
    
    /**
     * إضافة علامة مائية على صورة
     */
    public function addWatermark($imagePath, $outputPath = null, $options = []) {
        // التحقق من وجود الصورة الأصلية
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'الصورة الأصلية غير موجودة'];
        }
        
        // التحقق من وجود شعار المنصة
        if (!file_exists($this->watermarkPath)) {
            return ['success' => false, 'error' => 'شعار المنصة غير موجود'];
        }
        
        // قراءة الخيارات
        $opacity = $options['opacity'] ?? $this->watermarkOpacity;
        $position = $options['position'] ?? $this->watermarkPosition;
        $size = $options['size'] ?? $this->watermarkSize;
        $padding = $options['padding'] ?? $this->watermarkPadding;
        
        // إنشاء الصورة من الملف
        $imageInfo = getimagesize($imagePath);
        $imageType = $imageInfo[2];
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return ['success' => false, 'error' => 'نوع الصورة غير مدعوم'];
        }
        
        if (!$sourceImage) {
            return ['success' => false, 'error' => 'فشل فتح الصورة'];
        }
        
        // قراءة شعار المنصة
        $watermarkInfo = getimagesize($this->watermarkPath);
        $watermarkType = $watermarkInfo[2];
        
        switch ($watermarkType) {
            case IMAGETYPE_JPEG:
                $watermarkImage = imagecreatefromjpeg($this->watermarkPath);
                break;
            case IMAGETYPE_PNG:
                $watermarkImage = imagecreatefrompng($this->watermarkPath);
                break;
            case IMAGETYPE_GIF:
                $watermarkImage = imagecreatefromgif($this->watermarkPath);
                break;
            default:
                imagedestroy($sourceImage);
                return ['success' => false, 'error' => 'نوع شعار المنصة غير مدعوم'];
        }
        
        if (!$watermarkImage) {
            imagedestroy($sourceImage);
            return ['success' => false, 'error' => 'فشل فتح شعار المنصة'];
        }
        
        // الحصول على أبعاد الصور
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);
        
        // حساب الحجم الجديد للعلامة المائية
        $newWatermarkWidth = ($sourceWidth * $size) / 100;
        $newWatermarkHeight = ($watermarkHeight * $newWatermarkWidth) / $watermarkWidth;
        
        // تغيير حجم العلامة المائية
        $resizedWatermark = imagecreatetruecolor($newWatermarkWidth, $newWatermarkHeight);
        
        // الحفاظ على الشفافية للـ PNG
        if ($watermarkType == IMAGETYPE_PNG) {
            imagealphablending($resizedWatermark, false);
            imagesavealpha($resizedWatermark, true);
        }
        
        imagecopyresampled(
            $resizedWatermark, $watermarkImage,
            0, 0, 0, 0,
            $newWatermarkWidth, $newWatermarkHeight,
            $watermarkWidth, $watermarkHeight
        );
        
        // حساب موقع العلامة المائية
        $positions = $this->calculatePosition(
            $sourceWidth, $sourceHeight,
            $newWatermarkWidth, $newWatermarkHeight,
            $position, $padding
        );
        
        // إضافة العلامة المائية مع الشفافية
        $this->imagecopymerge_alpha(
            $sourceImage, $resizedWatermark,
            $positions['x'], $positions['y'],
            0, 0,
            $newWatermarkWidth, $newWatermarkHeight,
            $opacity
        );
        
        // حفظ الصورة النهائية
        $outputPath = $outputPath ?? $imagePath;
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($sourceImage, $outputPath, 95);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($sourceImage, $outputPath, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($sourceImage, $outputPath);
                break;
        }
        
        // تحرير الذاكرة
        imagedestroy($sourceImage);
        imagedestroy($watermarkImage);
        imagedestroy($resizedWatermark);
        
        if ($result) {
            return ['success' => true, 'path' => $outputPath];
        } else {
            return ['success' => false, 'error' => 'فشل حفظ الصورة'];
        }
    }
    
    /**
     * حساب موقع العلامة المائية
     */
    private function calculatePosition($sourceWidth, $sourceHeight, $watermarkWidth, $watermarkHeight, $position, $padding) {
        switch ($position) {
            case 'center':
                $x = ($sourceWidth - $watermarkWidth) / 2;
                $y = ($sourceHeight - $watermarkHeight) / 2;
                break;
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
            case 'top-right':
                $x = $sourceWidth - $watermarkWidth - $padding;
                $y = $padding;
                break;
            case 'bottom-left':
                $x = $padding;
                $y = $sourceHeight - $watermarkHeight - $padding;
                break;
            case 'bottom-right':
            default:
                $x = $sourceWidth - $watermarkWidth - $padding;
                $y = $sourceHeight - $watermarkHeight - $padding;
                break;
        }
        
        return ['x' => $x, 'y' => $y];
    }
    
    /**
     * دمج الصور مع دعم الشفافية
     */
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        $pct /= 100;
        
        // الحصول على أبعاد الصورة المصدر
        $w = imagesx($src_im);
        $h = imagesy($src_im);
        
        // إنشاء صورة مؤقتة
        $cut = imagecreatetruecolor($src_w, $src_h);
        
        // نسخ جزء من الصورة الوجهة
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        
        // نسخ الصورة المصدر
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        
        // دمج الصورتين مع الشفافية
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct * 100);
        
        imagedestroy($cut);
    }
    
    /**
     * إضافة علامة مائية على جميع الصور في مجلد
     */
    public function processDirectory($directory, $outputDirectory = null, $options = []) {
        $outputDirectory = $outputDirectory ?? $directory;
        
        if (!is_dir($directory)) {
            return ['success' => false, 'error' => 'المجلد غير موجود'];
        }
        
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }
        
        $processed = 0;
        $failed = 0;
        $errors = [];
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $directory . '/' . $file;
            $outputPath = $outputDirectory . '/' . $file;
            
            if (is_file($filePath)) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $result = $this->addWatermark($filePath, $outputPath, $options);
                    
                    if ($result['success']) {
                        $processed++;
                    } else {
                        $failed++;
                        $errors[] = $file . ': ' . $result['error'];
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors
        ];
    }
    
    /**
     * إضافة علامة مائية نصية
     */
    public function addTextWatermark($imagePath, $text, $outputPath = null, $options = []) {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'الصورة الأصلية غير موجودة'];
        }
        
        // قراءة الخيارات
        $fontSize = $options['fontSize'] ?? 20;
        $fontColor = $options['fontColor'] ?? [255, 255, 255]; // RGB
        $backgroundColor = $options['backgroundColor'] ?? [0, 0, 0]; // RGB
        $opacity = $options['opacity'] ?? 30;
        $position = $options['position'] ?? 'bottom-right';
        $padding = $options['padding'] ?? 20;
        
        // إنشاء الصورة من الملف
        $imageInfo = getimagesize($imagePath);
        $imageType = $imageInfo[2];
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return ['success' => false, 'error' => 'نوع الصورة غير مدعوم'];
        }
        
        if (!$sourceImage) {
            return ['success' => false, 'error' => 'فشل فتح الصورة'];
        }
        
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        
        // إنشاء ألوان
        $textColor = imagecolorallocatealpha(
            $sourceImage,
            $fontColor[0], $fontColor[1], $fontColor[2],
            127 - ($opacity * 1.27)
        );
        
        // حساب موقع النص (استخدام الخط الافتراضي)
        $textWidth = imagefontwidth(5) * strlen($text);
        $textHeight = imagefontheight(5);
        
        switch ($position) {
            case 'center':
                $x = ($sourceWidth - $textWidth) / 2;
                $y = ($sourceHeight - $textHeight) / 2;
                break;
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
            case 'top-right':
                $x = $sourceWidth - $textWidth - $padding;
                $y = $padding;
                break;
            case 'bottom-left':
                $x = $padding;
                $y = $sourceHeight - $textHeight - $padding;
                break;
            case 'bottom-right':
            default:
                $x = $sourceWidth - $textWidth - $padding;
                $y = $sourceHeight - $textHeight - $padding;
                break;
        }
        
        // إضافة النص
        imagestring($sourceImage, 5, $x, $y, $text, $textColor);
        
        // حفظ الصورة
        $outputPath = $outputPath ?? $imagePath;
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($sourceImage, $outputPath, 95);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($sourceImage, $outputPath, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($sourceImage, $outputPath);
                break;
        }
        
        imagedestroy($sourceImage);
        
        if ($result) {
            return ['success' => true, 'path' => $outputPath];
        } else {
            return ['success' => false, 'error' => 'فشل حفظ الصورة'];
        }
    }
}

// مثال على الاستخدام
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $watermark = new WatermarkManager();
    
    // إضافة علامة مائية على صورة واحدة
    if (isset($_GET['image'])) {
        $imagePath = __DIR__ . '/' . $_GET['image'];
        $result = $watermark->addWatermark($imagePath);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // معالجة مجلد كامل
    if (isset($_GET['directory'])) {
        $directory = __DIR__ . '/' . $_GET['directory'];
        $result = $watermark->processDirectory($directory);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    echo "نظام العلامة المائية جاهز";
}
?>
