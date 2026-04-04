<?php
include "config/db.php";

$productImages = [
    "iPhone 11" => "iphone_11.png",
    "Samsung Galaxy S20" => "samsung_galaxy_s20.png",
    "Dell Inspiron Laptop" => "dell_inspiron_laptop.png",
    "HP Pavilion Laptop" => "hp_pavilion_laptop.png",
    "Wireless Mouse" => "wireless_mouse.png",
    "Bluetooth Speaker" => "bluetooth_speaker.png",
    "Noise Cancelling Headphones" => "noise_cancelling_headphones.png",
    "Power Bank" => "power_bank.png",
    "Smart Watch" => "smart_watch.png",
    "USB Keyboard" => "usb_keyboard.png",
    "Tablet" => "tablet.png",
    "Phone Charger" => "phone_charger.png",
    "Organic Chemistry Book" => "organic_chemistry_book.png",
    "Biology Textbook" => "biology_textbook.png",
    "Physics Guide" => "physics_guide.png",
    "Math Practice Book" => "math_practice_book.png",
    "English Novel" => "english_novel.png",
    "History Notes Book" => "history_notes_book.png",
    "Exam Preparation Book" => "exam_preparation_book.png",
    "Programming Basics Book" => "programming_basics_book.png",
    "Lab Manual" => "lab_manual.png",
    "Dictionary" => "dictionary.png",
    "Research Methods Book" => "research_methods_book.png",
    "Public Speaking Book" => "public_speaking_book.png",
    "Blue T-Shirt" => "blue_t_shirt.png",
    "Black Hoodie" => "black_hoodie.png",
    "Denim Jacket" => "denim_jacket.png",
    "White Shirt" => "white_shirt.png",
    "Casual Pants" => "casual_pants.png",
    "Slim Fit Jeans" => "slim_fit_jeans.png",
    "Winter Sweater" => "winter_sweater.png",
    "Track Pants" => "track_pants.png",
    "College Jacket" => "college_jacket.png",
    "Sports T-Shirt" => "sports_t_shirt.png",
    "Cotton Kurta" => "cotton_kurta.png",
    "Printed Shirt" => "printed_shirt.png",
    "Study Table" => "study_table.png",
    "Office Chair" => "office_chair.png",
    "Wooden Shelf" => "wooden_shelf.png",
    "Bedside Table" => "bedside_table.png",
    "Drawer Cabinet" => "drawer_cabinet.png",
    "Reading Desk" => "reading_desk.png",
    "Plastic Chair" => "plastic_chair.png",
    "Bookshelf" => "bookshelf.png",
    "Single Bed Frame" => "single_bed_frame.png",
    "Laptop Table" => "laptop_table.png",
    "Mirror Stand" => "mirror_stand.png",
    "Storage Rack" => "storage_rack.png",
    "Football" => "football.png",
    "Cricket Bat" => "cricket_bat.png",
    "Badminton Racket" => "badminton_racket.png",
    "Basketball" => "basketball.png",
    "Yoga Mat" => "yoga_mat.png",
    "Skipping Rope" => "skipping_rope.png",
    "Tennis Ball Set" => "tennis_ball_set.png",
    "Gym Gloves" => "gym_gloves.png",
    "Volleyball" => "volleyball.png",
    "Dumbbell Pair" => "dumbbell_pair.png",
    "Shin Guard" => "shin_guard.png",
    "Sports Bottle" => "sports_bottle.png",
    "Backpack" => "backpack.png",
    "Laptop Bag" => "laptop_bag.png",
    "Wallet" => "wallet.png",
    "Sunglasses" => "sunglasses.png",
    "Wrist Watch" => "wrist_watch.png",
    "Cap" => "cap.png",
    "Belt" => "belt.png",
    "Travel Mug" => "travel_mug.png",
    "Phone Stand" => "phone_stand.png",
    "Crossbody Bag" => "crossbody_bag.png",
    "Jewelry Box" => "jewelry_box.png",
    "Scarf" => "scarf.png",
    "Running Shoes" => "running_shoes.png",
    "Canvas Shoes" => "canvas_shoes.png",
    "Formal Shoes" => "formal_shoes.png",
    "White Sneakers" => "white_sneakers.png",
    "Sports Shoes" => "sports_shoes.png",
    "Slip-On Shoes" => "slip_on_shoes.png",
    "Hiking Shoes" => "hiking_shoes.png",
    "Sandals" => "sandals.png",
    "College Shoes" => "college_shoes.png",
    "Training Shoes" => "training_shoes.png",
    "Casual Sneakers" => "casual_sneakers.png",
    "Flat Shoes" => "flat_shoes.png",
    "Notebook Set" => "notebook_set.png",
    "Pen Pack" => "pen_pack.png",
    "Drawing Book" => "drawing_book.png",
    "Scientific Calculator" => "scientific_calculator.png",
    "Geometry Box" => "geometry_box.png",
    "Sticky Notes Pack" => "sticky_notes_pack.png",
    "Desk Organizer" => "desk_organizer.png",
    "Marker Set" => "marker_set.png",
    "Clipboard" => "clipboard.png",
    "File Folder Set" => "file_folder_set.png",
    "Journal Book" => "journal_book.png",
    "Whiteboard Kit" => "whiteboard_kit.png",
];

$updated = 0;

$res = $conn->query("SELECT id, name FROM products");
while ($row = $res->fetch_assoc()) {
    $productId = (int)$row['id'];
    $productName = $row['name'];

    foreach ($productImages as $baseName => $imageFile) {
        if (strpos($productName, $baseName) === 0) {
            $safeImage = $conn->real_escape_string($imageFile);
            if ($conn->query("UPDATE products SET image='$safeImage' WHERE id=$productId")) {
                $updated++;
            }
            break;
        }
    }
}

echo $updated . " products updated with matching images.";
?>
