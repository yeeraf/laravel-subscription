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

### สร้าง Package, PackagePrice ใหม่

### ผูก Package เข้ากับ Benefit พร้อมกำหนดค่า

### เขื่อม Package กับ Model (ทำการ Subscribe) และการยืนยันการ Subscribe

### การตรวจสอบ Benefit ที่มีจาก Model ที่ทำการ Subscribe Package ไว้

