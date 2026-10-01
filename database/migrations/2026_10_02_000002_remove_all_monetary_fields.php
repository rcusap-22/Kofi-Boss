<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('product_prices');
        if(Schema::hasColumn('inventory_items','unit_cost')) Schema::table('inventory_items',fn(Blueprint $t)=>$t->dropColumn('unit_cost'));
        if(Schema::hasColumn('purchases','total_amount')) Schema::table('purchases',fn(Blueprint $t)=>$t->dropColumn('total_amount'));
        if(Schema::hasColumn('purchase_items','unit_price')) Schema::table('purchase_items',fn(Blueprint $t)=>$t->dropColumn('unit_price'));
        if(Schema::hasColumn('sizes','price')) Schema::table('sizes',fn(Blueprint $t)=>$t->dropColumn('price'));
        if(Schema::hasColumn('add_ons','price')) Schema::table('add_ons',fn(Blueprint $t)=>$t->dropColumn('price'));
        Schema::table('orders',function(Blueprint $t){ if(Schema::hasColumn('orders','total_amount'))$t->dropColumn('total_amount'); if(Schema::hasColumn('orders','payment_method'))$t->dropColumn('payment_method'); });
        Schema::table('order_details',function(Blueprint $t){ if(Schema::hasColumn('order_details','unit_price'))$t->dropColumn('unit_price'); if(Schema::hasColumn('order_details','subtotal'))$t->dropColumn('subtotal'); });
        Schema::table('order_detail_addons',function(Blueprint $t){ if(Schema::hasColumn('order_detail_addons','addon_price'))$t->dropColumn('addon_price'); if(Schema::hasColumn('order_detail_addons','subtotal'))$t->dropColumn('subtotal'); });
    }
    public function down(): void {}
};
