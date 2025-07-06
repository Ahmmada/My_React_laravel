<?php

namespace Database\Seeders;
  
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
  
class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
           'شاشة المستخدمين',
           'اضافة مستخدم',
           'تعديل المستخدم',
           'حذف المستخدم',
           'ادوار المستخدمين',
           'اضافة دور',
           'تعديل الدور',
           'حذف الدور',
           'عرض الأذونات',
           'إنشاء إذن',
           'تعديل إذن',
           'حذف إذن'
        ];
        
        foreach ($permissions as $permission) {
             Permission::create(['name' => $permission]);
        }
    }
}
