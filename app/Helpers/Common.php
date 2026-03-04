<?php
namespace App\Helpers;

use App\Enums\AbsenceTypeFlag;
use App\Enums\AllowanceCategory;
use App\Enums\BillPeriod;
use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Enums\Religion;
use DateTime;

class Common
{
	private static $crypt_number = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	private static $crypt_hash = [
		"VTMxmJTx", // 0
		"iIX1SPoF", // 1
		"RoHebhiA", // 2
		"BRNhJWko", // 3
		"ybKFdaZA", // 4
		"paGKzvym", // 5
		"tZqJtdQF", // 6
		"HPnRLdwH", // 7
		"xrSwmZvY", // 8
		"eDaDETJM", // 9
	];

	public static function encrypt($val)
	{
		$encrypt = str_replace(self::$crypt_number, self::$crypt_hash, $val);
		return $encrypt;
	}

	public static function decrypt($val)
	{
		$decrypt = str_replace(self::$crypt_hash, self::$crypt_number, $val);
		return ($decrypt == $val) ? null : $decrypt;
	}

	public static function option($request)
	{
		$opt = [
			'month' => [
				'1' => __('label.january'),
				'2' => __('label.february'),
				'3' => __('label.march'),
				'4' => __('label.april'),
				'5' => __('label.may'),
				'6' => __('label.june'),
				'7' => __('label.july'),
				'8' => __('label.august'),
				'9' => __('label.september'),
				'10' => __('label.october'),
				'11' => __('label.november'),
				'12' => __('label.december')
			],
			'gender' => [
				Gender::Male->value => __('label.male'),
                Gender::Female->value => __('label.female'),
			],
            'education_level' => [
                'sd' => 'SD',
                'smp' => 'SMP',
                'sma' => 'SMA',
            ],
			'bill_period' => [
				BillPeriod::OneTime->value => __('label.one_time'),
				BillPeriod::Monthly->value => __('label.monthly'),
				BillPeriod::Semiannual->value => __('label.semiannual'),
			],
            'yesno' => [
                '1' => __('label.yes'),
                '0' => __('label.not'),
            ],
            'on_off' => [
                '1' => __('label.on'),
                '0' => __('label.off'),
            ],
            'activation' => [
                '1' => __('label.active'),
                '0' => __('label.not_active'),
            ],
            'religion' => [
                Religion::Islam->value => __('label.islam'),
                Religion::Kristen->value => __('label.kristen'),
                Religion::Hindu->value => __('label.hindu'),
                Religion::Budha->value => __('label.budha'),
            ],
            'marital_status' => [
                '0' => __('label.not_married'),
                '1' => __('label.married'),
            ],
            'employment_status' => [
                EmployeeStatus::Tetap->value => __('label.permanent'),
                EmployeeStatus::Honorer->value => __('label.honorer'),
                EmployeeStatus::Pengabdian->value => __('label.devotion'),
            ],
            'allowance_category' => [
                AllowanceCategory::Struktural->value => __('label.structural'),
                AllowanceCategory::Tanggungan->value => __('label.liability'),
                AllowanceCategory::Kinerja->value => __('label.performance'),
            ]
		];

        if ($request == 'year') {
            $years = [];

            for($y=2022; $y<=date('Y', strtotime('+2 year')); $y++)
                $years[$y] = $y;

            $opt['year'] = $years;
        }

		return $opt[$request];
	}

	public static function dateFormat($date, $format = 'dd mmmm yyyy', $lang='auto')
	{
		$time = strtotime($date);
		$day = date('d', $time);
		$dayName = self::dayFormat(date('N', $time), 'dddd', $lang);
		$mmmm = self::monthFormat(date('n', $time), 'mmmm', $lang);
		$mmm = self::monthFormat(date('n', $time), 'mmm', $lang);
		$mm = date('m', $time);
		$yyyy = date('Y', $time);
		$yy = date('y', $time);
		$hh = date('H', $time);
		$ii = date('i', $time);

		$search = ['day', 'dd', 'mmmm', 'mmm', 'mm', 'yyyy', 'yy', 'hh', 'ii'];
		$replace = [$dayName, $day, $mmmm, $mmm, $mm, $yyyy, $yy, $hh, $ii];

		return str_replace($search, $replace, $format);
	}

    public static function monthFormat($month, $format = 'mmmm', $lang='auto') // date('n')
    {
		if ($format == 'mmmm') {
			if ($lang == 'auto') {
				$fm = [
					__('label.january'),
					__('label.february'),
					__('label.march'),
					__('label.april'),
					__('label.may'),
					__('label.june'),
					__('label.july'),
					__('label.august'),
					__('label.september'),
					__('label.october'),
					__('label.november'),
					__('label.december'),
				];
			} else {
				$fm = [
					'January',
					'February',
					'March',
					'April',
					'May',
					'June',
					'July',
					'August',
					'September',
					'October',
					'November',
					'December',
				];
			}
		} elseif ($format == 'mmm') {
			if ($lang == 'auto') {
				$fm = [
					__('label.jan'),
					__('label.feb'),
					__('label.mar'),
					__('label.apr'),
					__('label.may'),
					__('label.jun'),
					__('label.jul'),
					__('label.aug'),
					__('label.sep'),
					__('label.oct'),
					__('label.nov'),
					__('label.dec'),
				];
			} else {
				$fm = [
					'Jan',
					'Feb',
					'Mar',
					'Apr',
					'May',
					'Jun',
					'Jul',
					'Aug',
					'Sep',
					'Oct',
					'Nov',
					'Dec',
				];
			}
		} elseif ($format == 'romawi') {
			$fm = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
		}

		return $fm[$month-1];
	}

	public static function dayFormat($day, $format = 'dddd', $lang='auto') // date('N')
    {
		if ($format == 'dddd') {
			$fd = [
				__('label.monday'),
				__('label.tuesday'),
				__('label.wednesday'),
				__('label.thursday'),
				__('label.friday'),
				__('label.saturday'),
				__('label.sunday'),
			];
		} elseif ($format == 'ddd') {
			$fd = [
				__('label.mon'),
				__('label.tue'),
				__('label.wed'),
				__('label.thu'),
				__('label.fri'),
				__('label.sat'),
				__('label.sun'),
			];
		}

		return $fd[$day-1];
	}

	public static function phoneFormat($phone)
	{
		$length = strlen($phone);
		$phone1 = substr($phone, 0, 4);
		$phone2 = substr($phone, 4, 4);
		$phone3 = substr($phone, 8, 4);
		$phone4 = ($length > 12) ? '-' . substr($phone, 12, $length-12) : '';

		return $phone1 . '-' . $phone2 . '-' . $phone3 . $phone4;
	}

	public static function phoneCorrection($phone)
	{
		$phone = str_replace(' ', '', $phone);
		$phone_code = substr($phone, 0, 2);
		$phone_code_plus = substr($phone, 0, 3);

		if ($phone_code == '62')
			$phone = '0' . substr($phone, 2, strlen($phone));
		else if ($phone_code_plus == '+62')
			$phone = '0' . substr($phone, 3, strlen($phone));

		return $phone;
    }

    public static function phoneAddCountryCode($phone)
    {
        $phone_format = self::phoneCorrection($phone);
        $phone = substr($phone_format, 1, strlen($phone_format));

        return '+62' . $phone;
    }

	public static function rounding($amount)
	{
		$hundreds = substr($amount, strlen($amount) - 3, 1) + 1;
		$round = substr($amount, 0, strlen($amount) - 3) . $hundreds . '00';

		return $round;
	}

	public static function randomString($len = 8)
	{
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
    }

    public static function terbilang($x)
    {
        $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];

        if ($x < 12)
            $terbilang = " " . $angka[$x];
        elseif ($x < 20)
            $terbilang = self::terbilang($x - 10) . " belas";
        elseif ($x < 100)
            $terbilang = self::terbilang($x / 10) . " puluh" . self::terbilang($x % 10);
        elseif ($x < 200)
            $terbilang = " seratus" . self::terbilang($x - 100);
        elseif ($x < 1000)
            $terbilang = self::terbilang($x / 100) . " ratus" . self::terbilang($x % 100);
        elseif ($x < 2000)
            $terbilang = " seribu" . self::terbilang($x - 1000);
        elseif ($x < 1000000)
            $terbilang = self::terbilang($x / 1000) . " ribu" . self::terbilang($x % 1000);
        elseif ($x < 1000000000)
            $terbilang = self::terbilang($x / 1000000) . " juta" . self::terbilang($x % 1000000);

        return ucwords($terbilang);
    }

    public static function decimalFormat($nominal, $decimal = 2)
    {
        if (strstr($nominal, '.')) {
            $result = number_format($nominal, $decimal, ',', '.');
        } else {
            $result = number_format($nominal, 0, '', '.');
        }

        return $result;
    }
}
