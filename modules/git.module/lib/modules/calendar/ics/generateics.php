<?php
namespace Git\Module\Modules\Calendar\Ics;

class  GenerateIcs
{

    const DT_FORMAT = 'Ymd\THis';
    protected $properties = array();
    private $available_properties = array(
        'description',
        'dtend',
        'dtstart',
        'location',
        'summary',
        'url',
        'SUMMARY;LANGUAGE=ru'
    );

    public function __construct($props)
    {
        $this->set($props);
    }

    public function set($key, $val = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (in_array($key, $this->available_properties)) {
                $this->properties[$key] = $this->sanitize_val($val, $key);
            }
        }
    }

    public function to_string($tmp_name = '')
    {
        $rows = $this->build_props($tmp_name);
        return implode("\r\n", $rows);
    }

    private function build_props($tmp_name = '')
    {
        $ics_props = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'X-WR-CALNAME:' . $tmp_name,
            'PRODID:-//TDR v1.0//RU',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'X-LIC-LOCATION:Europe/Moscow'
        );

        $props = array();
        foreach ($this->properties as $k => $v) {
            $props[strtoupper($k . ($k === 'url' ? ';VALUE=URI' : ''))] = $v;
        }

        // Set some default values
        $props['DTSTAMP'] = $this->format_timestamp('now');
        $props['UID'] = uniqid();
        // Append properties
        foreach ($props as $k => $v) {
            $ics_props[] = "$k:$v";
        }
        // Build ICS properties - add footer
        $ics_props[] = 'END:VEVENT';
        $ics_props[] = 'END:VCALENDAR';
        return $ics_props;
    }

    private function sanitize_val($val, $key = false)
    {
        switch ($key) {
            case 'dtend':
            case 'dtstamp':
            case 'dtstart':
                $val = $this->format_timestamp($val);
                break;
            default:
                $val = $this->escape_string($val);
        }
        return $val;
    }

    private function format_timestamp($timestamp)
    {
        $dt = new \DateTime($timestamp);
        return $dt->format(self::DT_FORMAT);
    }

    private function escape_string($str)
    {
        return preg_replace('/([\,;])/', '\\\$1', $str);
    }
}
?>