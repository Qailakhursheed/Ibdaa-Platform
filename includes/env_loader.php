<?php
/**
 * Environment Variables Loader
 * محمّل متغيرات البيئة
 * 
 * يقرأ ملف .env ويحمل المتغيرات إلى $_ENV و getenv()
 */

class EnvLoader {
    /**
     * تحميل ملف .env
     */
    public static function load($path) {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // تجاهل التعليقات والأسطر الفارغة
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // تحليل السطر (KEY=VALUE)
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // إزالة علامات التنصيص إن وجدت
                $value = self::stripQuotes($value);

                // حفظ المتغير
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * إزالة علامات التنصيص من القيمة
     */
    private static function stripQuotes($value) {
        if (strlen($value) < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * الحصول على قيمة متغير بيئة مع قيمة افتراضية
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // تحويل القيم المنطقية
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;

        return $value;
    }

    /**
     * التحقق من وجود متغير بيئة
     */
    public static function has($key) {
        return getenv($key) !== false;
    }
}

// تحميل ملف .env تلقائياً
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    try {
        EnvLoader::load($envPath);
    } catch (Exception $e) {
        // في حالة الفشل، استخدم القيم الافتراضية
        error_log("Failed to load .env file: " . $e->getMessage());
    }
}
