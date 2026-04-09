<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'ACCOUNT PAYABLE',
            'ACCOUNT RECEIVABLE',
            'AFTER SALES SERVICE',
            'BRAND & MARKETING STRATEGY',
            'BUSINESS CONTROL',
            'BUSINESS DEVELOPMENT',
            'BUSINESS INTELLIGENCE & FINANCIAL INFORMATION SYSTEM',
            'BUSINESS OPERATION',
            'COMPENSATION BENEFIT',
            'COMPLIANCE',
            'CUSTOMER DATA ANALYTICS',
            'CUSTOMER EXPERIENCE',
            'CUSTOMER OPERATIONS',
            'CUSTOMER OPERATIONS & EXPERIENCE',
            'CUSTOMER RELATIONSHIP CENTER',
            'DATA ANALYTICS',
            'DEMAND PLANNING',
            'DIGITAL & OMNICHANNEL',
            'DIGITAL MARKETING',
            'DIGITAL MARKETING - OMNICHANNEL',
            'DIGITAL, OMNICHANNEL & INTELLIGENT TECHNOLOGY',
            'DISTRIBUTION',
            'ECOMMERCE',
            'FASHION & ACCESSORIES',
            'FINANCE BUSINESS PARTNER',
            'FINANCE CONTROLLER',
            'FINANCIAL PLANNING',
            'FOOTWEAR & ACTIVE',
            'HR SERVICES & GA',
            'HRBP',
            'HUMAN RESOURCES',
            'INTERNAL AUDIT',
            'INVENTORY & COSTING',
            'IT DEVELOPER',
            'IT DEVELOPMENT',
            'IT DEVELOPMENT & APPLICATION',
            'IT INFRASTRUCTURE',
            'IT OPERATIONAL',
            'LEGAL',
            'LEGAL & COMPLIANCE',
            'LIFESTYLE',
            'MAINTENANCE',
            'MANAGEMENT',
            'MARKETING COMMUNICATION',
            'MARKETING SUPPORT',
            'MARKETPLACE',
            'MARKETPLACE SUPPORT',
            'MERCHANDISER',
            'MERCHANDISING',
            'MTI',
            'MTI FINANCE BUSINESS PARTNER',
            'MTI RETAIL',
            'NESPRESSO',
            'OD & HR ANALYTICS',
            'OPERATION',
            'OWN BRAND',
            'PLANNING',
            'PRODUCT & ENGINEERING',
            'PRODUCT AND ENGINEERING',
            'PROJECT',
            'PROJECT & MAINTENANCE',
            'PUBLIC RELATIONS',
            'RESTAURANT',
            'SALES',
            'SHIPPING',
            'SHIPPING KRI',
            'SHIPPING MTI/KGB',
            'TALENT ACQUISITION',
            'TAX',
            'TRAINING',
            'TRAINING & OPERATIONS EXCELLENCE',
            'TREASURY',
            'VISUAL MERCHANDISING',
            'WAREHOUSE',
            'WAREHOUSE OPERATION',
            'WAREHOUSE SYSTEM & ADMINISTRATION',
        ];

        foreach ($departments as $name) {
            DB::table('sop_departments')->updateOrInsert(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
