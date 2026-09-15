<?php

declare(strict_types=1);

namespace App\Services;

final class UserAgentParser
{
    private const BOT_PATTERNS = [
        'Googlebot' => '/Googlebot/i',
        'Bingbot' => '/bingbot/i',
        'Baiduspider' => '/Baiduspider/i',
        'YandexBot' => '/YandexBot/i',
        'Sogou' => '/Sogou/i',
        '360Spider' => '/360Spider/i',
        'Bytespider' => '/Bytespider/i',
        'DuckDuckBot' => '/DuckDuckBot/i',
        'Slurp' => '/Slurp/i',
        'Exabot' => '/Exabot/i',
        'Facebot' => '/Facebot/i',
        'Applebot' => '/Applebot/i',
        'SemrushBot' => '/SemrushBot/i',
        'AhrefsBot' => '/AhrefsBot/i',
        'MJ12bot' => '/MJ12bot/i',
        'DotBot' => '/DotBot/i',
        'PetalBot' => '/PetalBot/i',
        'YisouSpider' => '/YisouSpider/i',
        'crawler' => '/crawler/i',
        'spider' => '/spider/i',
    ];

    private const BRAND_PATTERNS = [
        'iPhone' => '/iPhone/i',
        'iPad' => '/iPad/i',
        'Huawei' => '/Huawei|HONOR|HUAWEI/i',
        'Xiaomi' => '/Xiaomi|Redmi|POCO|Mi[\s;]|2[0-9]{3}[A-Z0-9]{4,6}DC/i',
        'OPPO' => '/OPPO|Oppo|oppo|P[A-Z]{1}[0-9]{3,4}/i',
        'vivo' => '/vivo|VIVO|V[0-9]{4}[A-Z]?|iQOO/i',
        'Samsung' => '/Samsung|SAMSUNG|SM-[A-Z]|GT-[A-Z]/i',
        'OnePlus' => '/OnePlus|ONEPLUS/i',
        'Meizu' => '/Meizu|MEIZU/i',
        'ZTE' => '/ZTE|zte/i',
        'Sony' => '/Sony|SONY/i',
        'LG' => '/LG[-\s]/i',
        'Motorola' => '/Motorola|MOT-|XT/i',
        'Nokia' => '/Nokia|nokia/i',
        'Google' => '/Pixel|Nexus/i',
        'Realme' => '/Realme|realme|RMX/i',
        'Honor' => '/Honor|honor/i',
        'Tecno' => '/Tecno|TECNO/i',
        'Infinix' => '/Infinix|INFINIX/i',
        'Mac' => '/Macintosh|Mac OS X/i',
        'Windows PC' => '/Windows NT/i',
        'Linux' => '/Linux(?!.*Android)/i',
    ];

    public function parse(string $userAgent): array
    {
        if ($userAgent === '') {
            return $this->emptyResult();
        }

        $bot = $this->detectBot($userAgent);
        if ($bot !== null) {
            return [
                'device_type' => 'bot',
                'device_brand' => null,
                'browser' => $bot,
                'browser_version' => null,
                'os' => 'Bot',
                'os_version' => null,
                'is_bot' => true,
                'bot_name' => $bot,
            ];
        }

        $deviceType = $this->detectDeviceType($userAgent);
        $brand = $this->detectBrand($userAgent);
        $os = $this->detectOs($userAgent);
        $browser = $this->detectBrowser($userAgent);

        return [
            'device_type' => $deviceType,
            'device_brand' => $brand,
            'browser' => $browser['name'],
            'browser_version' => $browser['version'],
            'os' => $os['name'],
            'os_version' => $os['version'],
            'is_bot' => false,
            'bot_name' => null,
        ];
    }

    private function emptyResult(): array
    {
        return [
            'device_type' => 'unknown',
            'device_brand' => null,
            'browser' => 'Unknown',
            'browser_version' => null,
            'os' => 'Unknown',
            'os_version' => null,
            'is_bot' => false,
            'bot_name' => null,
        ];
    }

    private function detectBot(string $ua): ?string
    {
        foreach (self::BOT_PATTERNS as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }

        if (preg_match('/bot|crawl|spider|slurp|mediapartners/i', $ua) && !preg_match('/Cubot/i', $ua)) {
            return 'Unknown Bot';
        }

        return null;
    }

    private function detectDeviceType(string $ua): string
    {
        if (preg_match('/tablet|iPad|Playbook|Silk(?!.*Mobile)/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|BlackBerry|Opera Mini|Windows Phone|webOS|BB10|MiuiBrowser|HuaweiBrowser/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/Android/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrand(string $ua): ?string
    {
        foreach (self::BRAND_PATTERNS as $brand => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $brand;
            }
        }

        return null;
    }

    private function detectOs(string $ua): array
    {
        // iOS
        if (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) {
            return ['name' => 'iOS', 'version' => str_replace('_', '.', $m[1])];
        }
        if (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m)) {
            return ['name' => 'iPadOS', 'version' => str_replace('_', '.', $m[1])];
        }
        if (preg_match('/(?:Mac OS X|Macintosh).*?([\d_]+(?:[\d_]+)?)/i', $ua, $m)) {
            $ver = str_replace('_', '.', $m[1]);
            if (str_starts_with($ver, '10.16')) {
                $ver = '12+'. $ver;
            }

            return ['name' => 'macOS', 'version' => $ver];
        }

        // Android
        if (preg_match('/Android[\s-]([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Android', 'version' => $m[1]];
        }
        if (preg_match('/Android/i', $ua)) {
            return ['name' => 'Android', 'version' => null];
        }

        // Windows
        if (preg_match('/Windows NT 10\.0/i', $ua)) {
            if (preg_match('/Windows NT 10\.0.*?22\d{3}/i', $ua)) {
                return ['name' => 'Windows', 'version' => '11'];
            }

            return ['name' => 'Windows', 'version' => '10'];
        }
        if (preg_match('/Windows NT 6\.3/i', $ua)) {
            return ['name' => 'Windows', 'version' => '8.1'];
        }
        if (preg_match('/Windows NT 6\.2/i', $ua)) {
            return ['name' => 'Windows', 'version' => '8'];
        }
        if (preg_match('/Windows NT 6\.1/i', $ua)) {
            return ['name' => 'Windows', 'version' => '7'];
        }
        if (preg_match('/Windows NT 6\.0/i', $ua)) {
            return ['name' => 'Windows', 'version' => 'Vista'];
        }
        if (preg_match('/Windows NT 5\.1/i', $ua)) {
            return ['name' => 'Windows', 'version' => 'XP'];
        }
        if (preg_match('/Windows\s*(?:Phone)?[\s]*([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Windows', 'version' => $m[1]];
        }
        if (preg_match('/Windows/i', $ua)) {
            return ['name' => 'Windows', 'version' => null];
        }

        // HarmonyOS
        if (preg_match('/HarmonyOS/i', $ua)) {
            $ver = null;
            if (preg_match('/HarmonyOS[\s/]([\d.]+)/i', $ua, $m)) {
                $ver = $m[1];
            }

            return ['name' => 'HarmonyOS', 'version' => $ver];
        }

        // Linux
        if (preg_match('/Linux/i', $ua) && !preg_match('/Android/i', $ua)) {
            $distro = 'Linux';
            if (preg_match('/Ubuntu/i', $ua)) $distro = 'Ubuntu';
            elseif (preg_match('/Fedora/i', $ua)) $distro = 'Fedora';
            elseif (preg_match('/Debian/i', $ua)) $distro = 'Debian';
            elseif (preg_match('/CentOS/i', $ua)) $distro = 'CentOS';
            elseif (preg_match('/Mint/i', $ua)) $distro = 'Linux Mint';

            return ['name' => $distro, 'version' => null];
        }

        // Chrome OS
        if (preg_match('/CrOS/i', $ua)) {
            return ['name' => 'Chrome OS', 'version' => null];
        }

        // FreeBSD
        if (preg_match('/FreeBSD/i', $ua)) {
            return ['name' => 'FreeBSD', 'version' => null];
        }

        return ['name' => 'Unknown', 'version' => null];
    }

    private function detectBrowser(string $ua): array
    {
        // In-app browsers first (higher priority)

        // WeChat
        if (preg_match('/MicroMessenger\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'WeChat', 'version' => $m[1]];
        }

        // QQ Browser / QQ内置浏览器
        if (preg_match('/QQ\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'QQ', 'version' => $m[1]];
        }
        if (preg_match('/MQQBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'QQ Browser', 'version' => $m[1]];
        }

        // Alipay
        if (preg_match('/AlipayClient\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Alipay', 'version' => $m[1]];
        }

        // DingTalk
        if (preg_match('/DingTalk\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'DingTalk', 'version' => $m[1]];
        }

        // Douyin / TikTok
        if (preg_match('/BytedanceWebview|TikTok|Douyin/i', $ua, $m)) {
            $ver = null;
            if (preg_match('/BytedanceWebview\/([\d.]+)/i', $ua, $m2)) $ver = $m2[1];
            return ['name' => 'Douyin', 'version' => $ver];
        }

        // Weibo
        if (preg_match('/Weibo/i', $ua)) {
            $ver = null;
            if (preg_match('/Weibo.*?([\d.]+)/i', $ua, $m)) $ver = $m[1];
            return ['name' => 'Weibo', 'version' => $ver];
        }

        // Baidu App
        if (preg_match('/baiduboxapp\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Baidu App', 'version' => $m[1]];
        }

        // UC Browser
        if (preg_match('/UCBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'UC Browser', 'version' => $m[1]];
        }

        // Sogou Browser
        if (preg_match('/SogouMobileBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Sogou Mobile', 'version' => $m[1]];
        }
        if (preg_match('/SE\s([\d.]+).*Sogou/i', $ua, $m)) {
            return ['name' => 'Sogou', 'version' => $m[1]];
        }

        // 360 Browser
        if (preg_match('/QIHU|360SE|360EE/i', $ua)) {
            $ver = null;
            if (preg_match('/360[SE]E\/([\d.]+)/i', $ua, $m)) $ver = $m[1];
            return ['name' => '360 Browser', 'version' => $ver];
        }

        // Maxthon
        if (preg_match('/Maxthon\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Maxthon', 'version' => $m[1]];
        }

        //主流浏览器

        // Edge (must check before Chrome)
        if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Edge', 'version' => $m[1]];
        }

        // Opera / OPR
        if (preg_match('/(?:OPR|Opera)\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Opera', 'version' => $m[1]];
        }

        // Vivaldi
        if (preg_match('/Vivaldi\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Vivaldi', 'version' => $m[1]];
        }

        // Brave (uses Chrome UA, check for Brave-specific)
        if (preg_match('/Brave(?:\/([\d.]+))?/i', $ua, $m)) {
            return ['name' => 'Brave', 'version' => $m[1] ?? null];
        }

        // Yandex Browser
        if (preg_match('/YaBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Yandex', 'version' => $m[1]];
        }

        // Samsung Internet
        if (preg_match('/SamsungBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Samsung Internet', 'version' => $m[1]];
        }

        // MiuiBrowser
        if (preg_match('/MiuiBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'MIUI Browser', 'version' => $m[1]];
        }

        // Huawei Browser
        if (preg_match('/HuaweiBrowser\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Huawei Browser', 'version' => $m[1]];
        }

        // Firefox
        if (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Firefox', 'version' => $m[1]];
        }
        if (preg_match('/FxiOS\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Firefox iOS', 'version' => $m[1]];
        }

        // Chrome (check after Edge/Opera/Brave)
        if (preg_match('/(?:CriOS|Chrome)\/([\d.]+)/i', $ua, $m)) {
            return ['name' => 'Chrome', 'version' => $m[1]];
        }

        // Safari (must be last major check)
        if (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m)) {
            return ['name' => 'Safari', 'version' => $m[1]];
        }

        // IE
        if (preg_match('/MSIE\s([\d.]+)/i', $ua, $m)) {
            return ['name' => 'IE', 'version' => $m[1]];
        }
        if (preg_match('/Trident\/.*rv:([\d.]+)/i', $ua, $m)) {
            return ['name' => 'IE', 'version' => $m[1]];
        }

        return ['name' => 'Unknown', 'version' => null];
    }
}
