<?php
namespace App\Services\Membership;
class MembershipPackageFactory {
    public static function defaultPackages(): array { return [
        ['code'=>'PKG_1_MONTH','name'=>'1 Month Basic','duration_months'=>1,'price'=>250000,'free_class_access'=>false,'benefits'=>['Gym access for 1 month']],
        ['code'=>'PKG_3_MONTHS','name'=>'3 Months Basic','duration_months'=>3,'price'=>675000,'free_class_access'=>false,'benefits'=>['Gym access for 3 months']],
        ['code'=>'PKG_6_MONTHS_PLUS','name'=>'6 Months Plus','duration_months'=>6,'price'=>1200000,'free_class_access'=>true,'benefits'=>['Gym access for 6 months','Free access to yoga and aerobics classes']],
        ['code'=>'PKG_12_MONTHS_PREMIUM','name'=>'12 Months Premium','duration_months'=>12,'price'=>2200000,'free_class_access'=>true,'benefits'=>['Gym access for 12 months','Free access to yoga and aerobics classes']],
    ]; }
}
