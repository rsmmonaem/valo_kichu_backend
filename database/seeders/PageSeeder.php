<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Terms and Conditions
        // Page::firstOrCreate(
        //     ['page_type' => Page::TYPE_TERMS_AND_CONDITIONS],
        //     [
        //         'title' => 'Terms and Conditions',
        //         'content' => '<h1>Terms and Conditions</h1><p>Welcome to our platform. By accessing and using this service, you agree to be bound by the following terms and conditions.</p><h2>1. Acceptance of Terms</h2><p>By using our service, you acknowledge that you have read, understood, and agree to be bound by these terms.</p><h2>2. User Responsibilities</h2><p>Users are responsible for maintaining the confidentiality of their account information and for all activities that occur under their account.</p><h2>3. Prohibited Activities</h2><p>Users may not use the service for any illegal or unauthorized purpose.</p>',
        //         'status' => true,
        //     ]
        // );

        // // Privacy Policy
        // Page::firstOrCreate(
        //     ['page_type' => Page::TYPE_PRIVACY_POLICY],
        //     [
        //         'title' => 'Privacy Policy',
        //         'content' => '<h1>Privacy Policy</h1><p>We are committed to protecting your privacy. This policy explains how we collect, use, and safeguard your information.</p><h2>1. Information We Collect</h2><p>We collect information that you provide directly to us, including name, email, and contact details.</p><h2>2. How We Use Your Information</h2><p>We use the information we collect to provide, maintain, and improve our services.</p><h2>3. Data Security</h2><p>We implement appropriate security measures to protect your personal information.</p><h2>4. Your Rights</h2><p>You have the right to access, update, or delete your personal information at any time.</p>',
        //         'status' => true,
        //     ]
        // );

        // // About Us
        // Page::firstOrCreate(
        //     ['page_type' => Page::TYPE_ABOUT_US],
        //     [
        //         'title' => 'About Us',
        //         'content' => '<h1>About Us</h1><p>Welcome to our platform! We are a leading B2B e-commerce solution provider committed to delivering excellence.</p><h2>Our Mission</h2><p>Our mission is to provide businesses with innovative solutions that drive growth and success.</p><h2>Our Vision</h2><p>To be the most trusted and reliable B2B platform in the industry.</p><h2>Our Values</h2><ul><li>Customer First</li><li>Innovation</li><li>Integrity</li><li>Excellence</li></ul><h2>Why Choose Us</h2><p>We offer cutting-edge technology, exceptional customer service, and a commitment to your success.</p>',
        //         'status' => true,
        //     ]
        // );

        // // Return Policy
        // Page::firstOrCreate(
        //     ['page_type' => Page::TYPE_RETURN_POLICY],
        //     [
        //         'title' => 'Return Policy',
        //         'content' => '<h1>Return Policy</h1><p>We want you to be completely satisfied with your purchase. If you are not satisfied, you may return eligible items within the specified time frame.</p><h2>Return Eligibility</h2><p>Items must be returned in their original condition, unused, and with all tags attached.</p><h2>Return Timeframe</h2><p>You have 30 days from the date of purchase to return eligible items.</p><h2>Return Process</h2><ol><li>Contact our customer service team</li><li>Receive return authorization</li><li>Package the item securely</li><li>Ship the item back to us</li></ol><h2>Refund Processing</h2><p>Refunds will be processed within 5-7 business days after we receive and inspect the returned item.</p>',
        //         'status' => true,
        //     ]
        // );
    }
}
