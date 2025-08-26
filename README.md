# Yeeraf Laravel Subscription
แพ็กเกจ Laravel สำหรับสร้างระบบสมัครสมาชิก/แพ็กเกจ (subscription) ที่รองรับราคาเป็นช่วงเวลา สิทธิประโยชน์ (benefits) แบบกำหนดช่วงเวลาได้ พร้อมบันทึกประวัติการเปลี่ยนแปลง

ลิขสิทธิ์: MIT ดูไฟล์ [LICENSE](LICENSE)

## Prerequisites
- Laravel 9.2+
- PHP 8.0+

## การเตรียม Database 
1. Application ของท่านจะต้องมีตาราง *users* เพื่อเป็นการอ้างอิงในการบันทึก Log

2. แพ็คเกจนี้จะสร้าง table ดังต่อไปนี้ โปรดตรวจสอบว่า database เดิมของท่านไม่มีตารางที่ชื่อซ้ำกัน
    - package_plans
    - package_plan_prices
    - benefits
    - benefit_package_plan
    - model_package_plan
    - model_package_plan_logs
    - benefit_package_plan_logs

## การติดตั้ง

1. ติดตั้งผ่าน composer
```bash
composer require yeeraf/laravel-subscription
```

2. รันคำสั่ง Migration
```bash
php artisan migrate
```

## คำอธิบายโมเดล
### PackagePlan
ตารางเก็บข้อมูลแพ็กเกจ ที่มีได้หลายราคา

### PackagePlanPrice
ตารางเก็บข้อมูลของราคาของแพ็กเกจ และปริมาณวันที่สามารถใช้แพ็กเกจนั้นๆ

### Benefit
ตารางเก็บข้อมูลสิทธิประโยชน์ (benefits) ที่มีอยู่ในแพ็กเกจ (plan)

### BenefitPackagePlan
ตารางเก็บความสัมพันธ์ระหว่างสิทธิประโยชน์ (benefits) และแพ็กเกจ (plan)

### BenefitPackagePlanLog
ตารางเก็บประวัติการเปลี่ยนแปลงสิทธิประโยชน์ของแพ็กเกจ

### ModelPackagePlan
ตารางเก็บข้อมูลการสมัครสมาชิก (subscription) ของโมเดล (model) ที่ผูกกับแพ็กเกจ (plan) และมีสถานะ (status) ที่เปลี่ยนแปลงได้

### ModelPackagePlanLog
ตารางเก็บประวัติการเปลี่ยนแปลงของการสมัครสมาชิก (subscription) ของโมเดล (model)

## ตัวอย่างการใช้งาน

### สร้าง Benefit ใหม่
ตัวอย่าง: สร้าง Benefit ชื่อ "max_user_count" ที่เป็นชนิดจำนวนเต็ม

```php
use Yeeraf\LaravelSubscription\Models\Benefit;

$benefit = Benefit::create([
    'name' => 'max_user_count',
    'type' => 'int',
    'description' => 'จำนวนผู้ใช้สูงสุดที่อนุญาต',
]);
```

ตัวอย่าง: สร้าง Benefit ชื่อ "max_salepage" ชนิด Float
```php
use Yeeraf\LaravelSubscription\Models\Benefit;

$benefit = Benefit::create([
    'name' => 'max_salepage',
    'type' => 'float',
    'description' => 'จำนวนเซลล์เพจที่สร้างได้',
]);
```

### สร้าง Package, PackagePrice ใหม่
ตัวอย่าง: สร้างแพ็กเกจ Pro และกำหนดราคาที่มีอายุ 30 วัน โดยเปิดขายตั้งแต่วันนี้จนถึงอีก 6 เดือนข้างหน้า

```php
use Illuminate\Support\Carbon;
use Yeeraf\LaravelSubscription\Models\PackagePlan;
use Yeeraf\LaravelSubscription\Models\PackagePlanPrice;

$plan = PackagePlan::create([
    'name' => 'Pro',
    'description' => 'แพ็กเกจ Pro',
]);

$price = PackagePlanPrice::create([
    'package_plan_id' => $plan->id,
    'price' => 199.00,
    'currency' => 'THB',
    'day_duration' => 30,
    'start_date' => Carbon::now()->subDay(),
    'end_date' => Carbon::now()->addMonths(6),
]);
```

### ผูก Package เข้ากับ Benefit พร้อมกำหนดค่า
ตัวอย่าง: ผูก Benefit "max_user_count" เข้ากับแพ็กเกจ Pro กำหนดค่าเป็น "10" มีผลตั้งแต่วันนี้ถึงอีก 6 เดือน

```php
use Illuminate\Support\Carbon;

// กำหนดค่า Benefit ให้กับแพ็กเกจ
$plan = PackagePlan::where("name", "Pro")->first();
$plan->assignBenefit('max_user_count', '10', Carbon::now(), Carbon::now()->addMonths(6));
```

ตัวอย่าง: ผูก Benefit "max_salepage" เข้ากับแพ็กเกจ Gold กำหนดสร้างได้ไม่จำกัด มีผลตั้งแต่วันนี้และไม่มีวันิ้นสุดกำหนดไว้ ณ ขณะนี้
```php
use Illuminate\Support\Carbon;

// ค่า INF ใช้กับ Benefit ชนิด Float เท่านั้น
// ไม่ส่งค่า EndDate เพื่อไม่กำหนดวันหมดอายุ
$plan = PackagePlan::where("name", "Gold")->first();
$plan->assignBenefit('max_salepage', "INF", Carbon::now());
```

### เชื่อม Package กับ Model (ทำการ Subscribe) และการยืนยันการ Subscribe
ก่อน Subscribe ให้เพิ่ม Trait ลงในโมเดลผู้ใช้ (เช่น app/Models/User.php) เพื่อให้โมเดลสามารถใช้งานฟีเจอร์ Subscription ได้

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\Model;
use Yeeraf\LaravelSubscription\Traits\HasPackagePlan;

class User extends Model
{
    use HasPackagePlan;
}
```

จากนั้นทำการ Subscribe โดยเริ่มต้นสถานะจะเป็น pending และเมื่อชำระเงิน/ยืนยันสำเร็จจึงค่อย activate

```php
use Illuminate\Support\Carbon;

// สร้าง/ดึงผู้ใช้
$user = User::find(1);

// สมัครแพ็กเกจ (สร้างเรคคอร์ดสถานะ pending)
$pendingSubscription = $user->subscribeToPackagePlan($price, Carbon::now(), auth()->user()->id);

// ยืนยันการสมัคร (เปลี่ยนสถานะเป็น active และกำหนด start_date/end_date อัตโนมัติจาก day_duration)
$activeSubscription = $pendingSubscription->activate(Carbon::now(), auth()->user()->id);
```

### การตรวจสอบ Benefit ที่มีจาก Model ที่ทำการ Subscribe Package ไว้
สามารถอ่านค่าของ Benefit ปัจจุบันจากโมเดลได้ทันที โดยระบบจะคืนค่าที่ cast แล้วตามชนิดของ Benefit (เช่น int/bool/string)

```php
// ตัวอย่าง: อ่านค่า max_user_count ของผู้ใช้
$value = $user->getBenefitValue('max_user_count'); // เช่น 10 (int)

// หรือดู Subscription ปัจจุบันของผู้ใช้
$current = $user->currentSubscription(); // คืนค่า ModelPackagePlan ที่ active ล่าสุด หรือ null
```

