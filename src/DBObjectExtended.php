<?php


/**
 * Description of DBObjectExtended
 *
 * @author drweb
 */
namespace losthost\telle;

class DBObjectExtended extends \losthost\DB\DBObject {

    const FMT_DEFAULT = 0;
    const FMT_LISTLINE = 1;
    const FMT_NEW = 2;
    const FMT_DELETED = 3;
    const FMT_SHORT = 4;
    
    public function asString($format=self::FMT_DEFAULT) {
        switch ($format) {
            case self::FMT_DEFAULT:
                return $this->stringDefault();
            case self::FMT_LISTLINE:
                return $this->stringListLine();
            case self::FMT_NEW:
                return $this->stringNew();
            case self::FMT_DELETED:
                return $this->stringDeleted();
            case self::FMT_SHORT:
                return $this->stringShort();
            default:
                return $this->stringCustom($format);
        }
    }
    
    public function stringDefault() {
        return __(
                losthost\DB\DB::classShortName($this) .'::stringDefault', 
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }
    
    public function stringListLine() {
        return __(
                losthost\DB\DB::classShortName($this) .'::stringListLine', 
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }
    
    public function stringNew() {
        return __(
                losthost\DB\DB::classShortName($this) .'::stringNew', 
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }
    
    public function stringDeleted() {
        return __(
                losthost\DB\DB::classShortName($this) .'::stringDeleted', 
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }

    public function stringShort() {
        return __(
                losthost\DB\DB::classShortName($this) .'::stringShort', 
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }
    
    public function stringCustom($format) {
        return __(
                losthost\DB\DB::classShortName($this). '::string'. $format,
                $this->asArray(__('d-m-Y H:i:s'), __('Да'), __('Нет')));
    }
}
