<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // DB::table('cities')->insert([
        //       // ======================= Chittagong Division =======================
        //     // Cumilla
        //     ['id'=>1,'name'=>'দেবিদ্বার','district_id'=>44],
        //     ['id'=>2,'name'=>'বরুড়া','district_id'=>44],
        //     ['id'=>3,'name'=>'ব্রাহ্মণপাড়া','district_id'=>44],
        //     ['id'=>4,'name'=>'চান্দিনা','district_id'=>44],
        //     ['id'=>5,'name'=>'চৌদ্দগ্রাম','district_id'=>44],
        //     ['id'=>6,'name'=>'দাউদকান্দি','district_id'=>44],
        //     ['id'=>7,'name'=>'হোমনা','district_id'=>44],
        //     ['id'=>8,'name'=>'লাকসাম','district_id'=>44],
        //     ['id'=>9,'name'=>'মুরাদনগর','district_id'=>44],
        //     ['id'=>10,'name'=>'নাঙ্গলকোট','district_id'=>44],
        //     ['id'=>11,'name'=>'কুমিল্লা সদর','district_id'=>44],
        //     ['id'=>12,'name'=>'মেঘনা','district_id'=>44],
        //     ['id'=>13,'name'=>'মনোহরগঞ্জ','district_id'=>44],
        //     ['id'=>14,'name'=>'সদর দক্ষিণ','district_id'=>44],
        //     ['id'=>15,'name'=>'তিতাস','district_id'=>44],
        //     ['id'=>16,'name'=>'বুড়িচং','district_id'=>44],
        //     ['id'=>17,'name'=>'লালমাই','district_id'=>44],

        //     // Feni
        //     ['id'=>18,'name'=>'ছাগলনাইয়া','district_id'=>46],
        //     ['id'=>19,'name'=>'ফেনী সদর','district_id'=>46],
        //     ['id'=>20,'name'=>'সোনাগাজী','district_id'=>46],
        //     ['id'=>21,'name'=>'ফুলগাজী','district_id'=>46],
        //     ['id'=>22,'name'=>'পরশুরাম','district_id'=>46],
        //     ['id'=>23,'name'=>'দাগনভূঞা','district_id'=>46],

        //     // Brahmanbaria
        //     ['id'=>24,'name'=>'ব্রাহ্মণবাড়িয়া সদর','district_id'=>41],
        //     ['id'=>25,'name'=>'কসবা','district_id'=>41],
        //     ['id'=>26,'name'=>'নাসিরনগর','district_id'=>41],
        //     ['id'=>27,'name'=>'সরাইল','district_id'=>41],
        //     ['id'=>28,'name'=>'আশুগঞ্জ','district_id'=>41],
        //     ['id'=>29,'name'=>'আখাউড়া','district_id'=>41],
        //     ['id'=>30,'name'=>'নবীনগর','district_id'=>41],
        //     ['id'=>31,'name'=>'বাঞ্ছারামপুর','district_id'=>41],
        //     ['id'=>32,'name'=>'বিজয়নগর','district_id'=>41],

        //     // Rangamati
        //     ['id'=>33,'name'=>'রাঙ্গামাটি সদর','district_id'=>50],
        //     ['id'=>34,'name'=>'কাপ্তাই','district_id'=>50],
        //     ['id'=>35,'name'=>'কাউখালী','district_id'=>50],
        //     ['id'=>36,'name'=>'বাঘাইছড়ি','district_id'=>50],
        //     ['id'=>37,'name'=>'বরকল','district_id'=>50],
        //     ['id'=>38,'name'=>'লংগদু','district_id'=>50],
        //     ['id'=>39,'name'=>'রাজস্থলী','district_id'=>50],
        //     ['id'=>40,'name'=>'বিলাইছড়ি','district_id'=>50],
        //     ['id'=>41,'name'=>'জুরাছড়ি','district_id'=>50],
        //     ['id'=>42,'name'=>'নানিয়ারচর','district_id'=>50],

        //     // Noakhali
        //     ['id'=>43,'name'=>'নোয়াখালী','district_id'=>49],
        //     ['id'=>44,'name'=>'কোম্পানীগঞ্জ','district_id'=>49],
        //     ['id'=>45,'name'=>'বেগমগঞ্জ','district_id'=>49],
        //     ['id'=>46,'name'=>'হাতিয়া','district_id'=>49],
        //     ['id'=>47,'name'=>'সুবর্ণচর','district_id'=>49],
        //     ['id'=>48,'name'=>'কবিরহাট','district_id'=>49],
        //     ['id'=>49,'name'=>'সেনবাগ','district_id'=>49],
        //     ['id'=>50,'name'=>'চাটখিল','district_id'=>49],
        //     ['id'=>51,'name'=>'সোনাইমুড়ী','district_id'=>49],

        //     // Chandpur
        //     ['id'=>52,'name'=>'হাইমচর','district_id'=>42],
        //     ['id'=>53,'name'=>'কচুয়া','district_id'=>42],
        //     ['id'=>54,'name'=>'শাহরাস্তি','district_id'=>42],
        //     ['id'=>55,'name'=>'চাঁদপুর সদর','district_id'=>42],
        //     ['id'=>56,'name'=>'মতলব','district_id'=>42],
        //     ['id'=>57,'name'=>'হাজীগঞ্জ','district_id'=>42],
        //     ['id'=>58,'name'=>'মতলব ফরিদগঞ্জ','district_id'=>42],

        //     // Lakshmipur
        //     ['id'=>59,'name'=>'লক্ষ্মীপুর সদর','district_id'=>48],
        //     ['id'=>60,'name'=>'কমলনগর','district_id'=>48],
        //     ['id'=>61,'name'=>'রায়পুর','district_id'=>48],
        //     ['id'=>62,'name'=>'রামগতি','district_id'=>48],
        //     ['id'=>63,'name'=>'রামগঞ্জ','district_id'=>48],

        //     // Chattogram
        //     ['id'=>64,'name'=>'রাঙ্গুনিয়া','district_id'=>43],
        //     ['id'=>65,'name'=>'সীতাকুন্ড','district_id'=>43],
        //     ['id'=>66,'name'=>'মীরসরাই','district_id'=>43],
        //     ['id'=>67,'name'=>'পটিয়া','district_id'=>43],
        //     ['id'=>68,'name'=>'সন্দ্বীপ','district_id'=>43],
        //     ['id'=>69,'name'=>'বাঁশখালী','district_id'=>43],
        //     ['id'=>70,'name'=>'বোয়ালখালী','district_id'=>43],
        //     ['id'=>71,'name'=>'আনোয়ারা','district_id'=>43],
        //     ['id'=>72,'name'=>'চন্দনাইশ','district_id'=>43],
        //     ['id'=>73,'name'=>'সাতকানিয়া','district_id'=>43],
        //     ['id'=>74,'name'=>'লোহাগাড়া','district_id'=>43],
        //     ['id'=>75,'name'=>'হাটহাজারী','district_id'=>43],
        //     ['id'=>76,'name'=>'ফটিকছড়ি','district_id'=>43],
        //     ['id'=>77,'name'=>'রাউজান','district_id'=>43],
        //     ['id'=>78,'name'=>'কর্ণফুলী','district_id'=>43],

        //     // Cox's Bazar
        //     ['id'=>79,'name'=>'কক্সবাজার সদর','district_id'=>45],
        //     ['id'=>80,'name'=>'চকরিয়া','district_id'=>45],
        //     ['id'=>81,'name'=>'কুতুবদিয়া','district_id'=>45],
        //     ['id'=>82,'name'=>'উখিয়া','district_id'=>45],
        //     ['id'=>83,'name'=>'মহেশখালী','district_id'=>45],
        //     ['id'=>84,'name'=>'পেকুয়া','district_id'=>45],
        //     ['id'=>85,'name'=>'রামু','district_id'=>45],
        //     ['id'=>86,'name'=>'টেকনাফ','district_id'=>45],

        //     // Khagrachari
        //     ['id'=>87,'name'=>'খাগড়াছড়ি সদর','district_id'=>47],
        //     ['id'=>88,'name'=>'দিঘীনালা','district_id'=>47],
        //     ['id'=>89,'name'=>'পানছড়ি','district_id'=>47],
        //     ['id'=>90,'name'=>'লক্ষীছড়ি','district_id'=>47],
        //     ['id'=>91,'name'=>'মাহালছড়ি','district_id'=>47],
        //     ['id'=>92,'name'=>'মানিকছড়ি','district_id'=>47],
        //     ['id'=>93,'name'=>'রামগড়','district_id'=>47],
        //     ['id'=>94,'name'=>'মাটিরাঙ্গা','district_id'=>47],
        //     ['id'=>95,'name'=>'গুইমারা','district_id'=>47],

        //     // Bandarban
        //     ['id'=>96,'name'=>'বান্দরবান সদর','district_id'=>40],
        //     ['id'=>97,'name'=>'আলীকদম','district_id'=>40],
        //     ['id'=>98,'name'=>'নাইক্ষ্যংছড়ি','district_id'=>40],
        //     ['id'=>99,'name'=>'রোয়াংছড়ি','district_id'=>40],
        //     ['id'=>100,'name'=>'লামা','district_id'=>40],
        //     ['id'=>101,'name'=>'রুমা','district_id'=>40],
        //     ['id'=>102,'name'=>'থানচি','district_id'=>40],

        //     // ======================= Rajshahi Division =======================
        //     // Sirajganj 
        //     ['id'=>103,'name'=>'বেলকুচি','district_id'=>25],
        //     ['id'=>104,'name'=>'চৌহালি','district_id'=>25],
        //     ['id'=>105,'name'=>'কামারখন্দ','district_id'=>25],
        //     ['id'=>106,'name'=>'কাজীপুর','district_id'=>25],
        //     ['id'=>107,'name'=>'রায়গঞ্জ','district_id'=>25],
        //     ['id'=>108,'name'=>'শাহজাদপুর','district_id'=>25],
        //     ['id'=>109,'name'=>'সিরাজগঞ্জ','district_id'=>25],
        //     ['id'=>110,'name'=>'তাড়াশ','district_id'=>25],
        //     ['id'=>111,'name'=>'উল্লাপাড়া','district_id'=>25],

        //     //  Pabna 
        //     ['id'=>112,'name'=>'সুজানগর','district_id'=>23],
        //     ['id'=>113,'name'=>'ঈশ্বরদী','district_id'=>23],
        //     ['id'=>114,'name'=>'ভাঙ্গুড়া','district_id'=>23],
        //     ['id'=>115,'name'=>'পাবনা সদর','district_id'=>23],
        //     ['id'=>116,'name'=>'বেড়া','district_id'=>23],
        //     ['id'=>117,'name'=>'আটঘরিয়া','district_id'=>23],
        //     ['id'=>118,'name'=>'চাটমোহর','district_id'=>23],
        //     ['id'=>119,'name'=>'সাঁথিয়া','district_id'=>23],
        //     ['id'=>120,'name'=>'ফরিদপুর','district_id'=>23],

        //     //  Bogura 
        //     ['id'=>121,'name'=>'কাহালু উপজেলা','district_id'=>18],
        //     ['id'=>122,'name'=>'বগুড়া সদর','district_id'=>18],
        //     ['id'=>123,'name'=>'সারিয়াকান্দি','district_id'=>18],
        //     ['id'=>124,'name'=>'শাজাহানপুর','district_id'=>18],
        //     ['id'=>125,'name'=>'দুপচাচিঁয়া উপজেলা','district_id'=>18],
        //     ['id'=>126,'name'=>'আদমদিঘি উপজেলা','district_id'=>18],
        //     ['id'=>127,'name'=>'নন্দিগ্রাম','district_id'=>18],
        //     ['id'=>128,'name'=>'সোনাতলা উপজেলা','district_id'=>18],
        //     ['id'=>129,'name'=>'ধুনট উপজেলা','district_id'=>18],
        //     ['id'=>130,'name'=>'গাবতলী','district_id'=>18],
        //     ['id'=>131,'name'=>'শেরপুর উপজেলা','district_id'=>18],
        //     ['id'=>132,'name'=>'শিবগঞ্জ','district_id'=>18],

        //     //  Rajshahi 
        //     ['id'=>133,'name'=>'পবা উপজেলা','district_id'=>24],
        //     ['id'=>134,'name'=>'দুর্গাপুর উপজেলা','district_id'=>24],
        //     ['id'=>135,'name'=>'মোহনপুর উপজেলা','district_id'=>24],
        //     ['id'=>136,'name'=>'চারঘাট উপজেলা','district_id'=>24],
        //     ['id'=>137,'name'=>'পুঠিয়া উপজেলা','district_id'=>24],
        //     ['id'=>138,'name'=>'বাঘা উপজেলা','district_id'=>24],
        //     ['id'=>139,'name'=>'গোদাগাড়ী উপজেলা','district_id'=>24],
        //     ['id'=>140,'name'=>'তানোর উপজেলা','district_id'=>24],
        //     ['id'=>141,'name'=>'বাগমারা উপজেলা','district_id'=>24],

        //     //  Natore 
        //     ['id'=>142,'name'=>'নাটোর সদর','district_id'=>21],
        //     ['id'=>143,'name'=>'সিংড়া','district_id'=>21],
        //     ['id'=>144,'name'=>'বড়াইগ্রাম','district_id'=>21],
        //     ['id'=>145,'name'=>'বাগাতিপাড়া','district_id'=>21],
        //     ['id'=>146,'name'=>'লালপুর','district_id'=>21],
        //     ['id'=>147,'name'=>'গুরুদাসপুর','district_id'=>21],
        //     ['id'=>148,'name'=>'নলডাঙ্গা','district_id'=>21],

        //     //  Joypurhat 
        //     ['id'=>149,'name'=>'আক্কেলপুর উপজেলা','district_id'=>19],
        //     ['id'=>150,'name'=>'কালাই উপজেলা','district_id'=>19],
        //     ['id'=>151,'name'=>'ক্ষেতলাল উপজেলা','district_id'=>19],
        //     ['id'=>152,'name'=>'পাঁচবিবি উপজেলা','district_id'=>19],
        //     ['id'=>153,'name'=>'জয়পুরহাট সদর','district_id'=>19],

        //     //  Chapainawabganj 
        //     ['id'=>154,'name'=>'চাঁপাইনবাবগঞ্জ সদর','district_id'=>22],
        //     ['id'=>155,'name'=>'গোমস্তাপুর','district_id'=>22],
        //     ['id'=>156,'name'=>'নাচোল','district_id'=>22],
        //     ['id'=>157,'name'=>'ভোলাহাট','district_id'=>22],
        //     ['id'=>158,'name'=>'শিবগঞ্জ','district_id'=>22],

        //     //  Naogaon 
        //     ['id'=>159,'name'=>'মহাদেবপুর উপজেলা','district_id'=>20],
        //     ['id'=>160,'name'=>'বদলগাছী উপজেলা','district_id'=>20],
        //     ['id'=>161,'name'=>'পত্নিতলা উপজেলা','district_id'=>20],
        //     ['id'=>162,'name'=>'ধামইরহাট উপজেলা','district_id'=>20],
        //     ['id'=>163,'name'=>'নিয়ামতপুর উপজেলা','district_id'=>20],
        //     ['id'=>164,'name'=>'মান্দা উপজেলা','district_id'=>20],
        //     ['id'=>165,'name'=>'আত্রাই উপজেলা','district_id'=>20],
        //     ['id'=>166,'name'=>'রাণীনগর উপজেলা','district_id'=>20],
        //     ['id'=>167,'name'=>'নওগাঁ সদর','district_id'=>20],
        //     ['id'=>168,'name'=>'পোরশা উপজেলা','district_id'=>20],
        //     ['id'=>169,'name'=>'সাপাহার','district_id'=>20],

        //     // ======================= Khulna Division =======================
        //     // Jessore
        //     ['id'=>170,'name'=>'Manirampur','district_id'=>26],
        //     ['id'=>171,'name'=>'Abhaynagar','district_id'=>26],
        //     ['id'=>172,'name'=>'Bagharpara','district_id'=>26],
        //     ['id'=>173,'name'=>'Chaugacha','district_id'=>26],
        //     ['id'=>174,'name'=>'Jhikargacha','district_id'=>26],
        //     ['id'=>175,'name'=>'Keshabpur','district_id'=>26],
        //     ['id'=>176,'name'=>'Jessore Sadar','district_id'=>26],
        //     ['id'=>177,'name'=>'Sharsha','district_id'=>26],

        //     // Satkhira
        //     ['id'=>178,'name'=>'Ashashuni','district_id'=>27],
        //     ['id'=>179,'name'=>'Debhata','district_id'=>27],
        //     ['id'=>180,'name'=>'Kalaroa','district_id'=>27],
        //     ['id'=>181,'name'=>'Satkhira Sadar','district_id'=>27],
        //     ['id'=>182,'name'=>'Shyamnagar','district_id'=>27],
        //     ['id'=>183,'name'=>'Tala','district_id'=>27],
        //     ['id'=>184,'name'=>'Kaliganj','district_id'=>27],

        //     // Meherpur
        //     ['id'=>185,'name'=>'Mujibnagar','district_id'=>28],
        //     ['id'=>186,'name'=>'Meherpur Sadar','district_id'=>28],
        //     ['id'=>187,'name'=>'Gangni','district_id'=>28],

        //     // Narail
        //     ['id'=>188,'name'=>'Narail Sadar','district_id'=>29],
        //     ['id'=>189,'name'=>'Lohagara','district_id'=>29],
        //     ['id'=>190,'name'=>'Kalia','district_id'=>29],

        //     // Chuadanga
        //     ['id'=>191,'name'=>'Chuadanga Sadar','district_id'=>30],
        //     ['id'=>192,'name'=>'Alamdanga','district_id'=>30],
        //     ['id'=>193,'name'=>'Damurhuda','district_id'=>30],
        //     ['id'=>194,'name'=>'Jibannagar','district_id'=>30],

        //     // Kushtia
        //     ['id'=>195,'name'=>'Kushtia Sadar','district_id'=>31],
        //     ['id'=>196,'name'=>'Kumarkhali','district_id'=>31],
        //     ['id'=>197,'name'=>'Khoksa','district_id'=>31],
        //     ['id'=>198,'name'=>'Mirpur','district_id'=>31],
        //     ['id'=>199,'name'=>'Daulatpur','district_id'=>31],
        //     ['id'=>200,'name'=>'Bheramara','district_id'=>31],

        //     // Magura
        //     ['id'=>201,'name'=>'Shalikha','district_id'=>32],
        //     ['id'=>202,'name'=>'Shripur','district_id'=>32],
        //     ['id'=>203,'name'=>'Magura Sadar','district_id'=>32],
        //     ['id'=>204,'name'=>'Mohammadpur','district_id'=>32],

        //     // Khulna
        //     ['id'=>205,'name'=>'Paikgachha','district_id'=>33],
        //     ['id'=>206,'name'=>'Phultala','district_id'=>33],
        //     ['id'=>207,'name'=>'Dighalia','district_id'=>33],
        //     ['id'=>208,'name'=>'Rupsha','district_id'=>33],
        //     ['id'=>209,'name'=>'Terokhada','district_id'=>33],
        //     ['id'=>210,'name'=>'Dumuria','district_id'=>33],
        //     ['id'=>211,'name'=>'Batiaghata','district_id'=>33],
        //     ['id'=>212,'name'=>'Dakop','district_id'=>33],
        //     ['id'=>213,'name'=>'Koira','district_id'=>33],

        //     // Bagerhat
        //     ['id'=>214,'name'=>'Fakirhat','district_id'=>34],
        //     ['id'=>215,'name'=>'Bagerhat Sadar','district_id'=>34],
        //     ['id'=>216,'name'=>'Mollahat','district_id'=>34],
        //     ['id'=>217,'name'=>'Sharankhola','district_id'=>34],
        //     ['id'=>218,'name'=>'Rampal','district_id'=>34],
        //     ['id'=>219,'name'=>'Morrelganj','district_id'=>34],
        //     ['id'=>220,'name'=>'Kachua','district_id'=>34],
        //     ['id'=>221,'name'=>'Mongla','district_id'=>34],
        //     ['id'=>222,'name'=>'Chitalmari','district_id'=>34],

        //     // Jhenaidah
        //     ['id'=>223,'name'=>'Jhenaidah Sadar','district_id'=>35],
        //     ['id'=>224,'name'=>'Shailkupa','district_id'=>35],
        //     ['id'=>225,'name'=>'Harinakundu','district_id'=>35],
        //     ['id'=>226,'name'=>'Kaliganj','district_id'=>35],
        //     ['id'=>227,'name'=>'Kotchandpur','district_id'=>35],
        //     ['id'=>228,'name'=>'Moheshpur','district_id'=>35],


        //     // ======================= Barishal Division =======================

        //     // Jhalokathi
        //     ['id'=>271,'name'=>'Jhalokathi Sadar','district_id'=>37],
        //     ['id'=>272,'name'=>'Kathalia','district_id'=>37],
        //     ['id'=>273,'name'=>'Nalchiti','district_id'=>37],
        //     ['id'=>274,'name'=>'Rajapur','district_id'=>37],

        //     // Patuakhali
        //     ['id'=>275,'name'=>'Bauphal','district_id'=>38],
        //     ['id'=>276,'name'=>'Patuakhali Sadar','district_id'=>38],
        //     ['id'=>277,'name'=>'Dumki','district_id'=>38],
        //     ['id'=>278,'name'=>'Dashmina','district_id'=>38],
        //     ['id'=>279,'name'=>'Kalapara','district_id'=>38],
        //     ['id'=>280,'name'=>'Mirzaganj','district_id'=>38],
        //     ['id'=>281,'name'=>'Galachipa','district_id'=>38],
        //     ['id'=>282,'name'=>'Rangabali','district_id'=>38],

        //     // Pirojpur
        //     ['id'=>283,'name'=>'Pirojpur Sadar','district_id'=>39],
        //     ['id'=>284,'name'=>'Nazirpur','district_id'=>39],
        //     ['id'=>285,'name'=>'Kaukhali','district_id'=>39],
        //     ['id'=>286,'name'=>'Bhandaria','district_id'=>39],
        //     ['id'=>287,'name'=>'Mathbaria','district_id'=>39],
        //     ['id'=>288,'name'=>'Nesarabad','district_id'=>39],
        //     ['id'=>289,'name'=>'Indurkani','district_id'=>39],

        //     // Barishal
        //     ['id'=>290,'name'=>'Barishal Sadar','district_id'=>35],
        //     ['id'=>291,'name'=>'Bakerganj','district_id'=>35],
        //     ['id'=>292,'name'=>'Babuganj','district_id'=>35],
        //     ['id'=>293,'name'=>'Uzirpur','district_id'=>35],
        //     ['id'=>294,'name'=>'Banaripara','district_id'=>35],
        //     ['id'=>295,'name'=>'Gournadi','district_id'=>35],
        //     ['id'=>296,'name'=>'Agailjhara','district_id'=>35],
        //     ['id'=>297,'name'=>'Mehendiganj','district_id'=>35],
        //     ['id'=>298,'name'=>'Muladi','district_id'=>35],
        //     ['id'=>299,'name'=>'Hijla','district_id'=>35],

        //     // Bhola
        //     ['id'=>300,'name'=>'Bhola Sadar','district_id'=>36],
        //     ['id'=>301,'name'=>'Borhanuddin','district_id'=>36],
        //     ['id'=>302,'name'=>'Char Fasson','district_id'=>36],
        //     ['id'=>303,'name'=>'Daulatkhan','district_id'=>36],
        //     ['id'=>304,'name'=>'Monpura','district_id'=>36],
        //     ['id'=>305,'name'=>'Tazumuddin','district_id'=>36],
        //     ['id'=>306,'name'=>'Lalmohan','district_id'=>36],

        //     // Barguna
        //     ['id'=>307,'name'=>'Amtali','district_id'=>34],
        //     ['id'=>308,'name'=>'Barguna Sadar','district_id'=>34],
        //     ['id'=>309,'name'=>'Betagi','district_id'=>34],
        //     ['id'=>310,'name'=>'Bamna','district_id'=>34],
        //     ['id'=>311,'name'=>'Patharghata','district_id'=>34],
        //     ['id'=>312,'name'=>'Taltali','district_id'=>34],





        // ]);
    }
}



// incomplete