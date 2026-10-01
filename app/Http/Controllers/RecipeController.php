<?php
namespace App\Http\Controllers;
use App\Models\{AddOn, AddOnRecipe, InventoryItem, Product, Size};
class RecipeController extends Controller
{
    public function index()
    {
        $products=Product::where('status','active')->with(['recipes.item','recipes.size'])->orderBy('category')->orderBy('name')->get();
        $sizes=Size::orderBy('id')->get();
        $extraShot=AddOn::firstOrCreate(['name'=>'Extra Espresso Shot']);
        $beans=InventoryItem::where('name','Coffee Beans')->firstOrFail();
        AddOnRecipe::updateOrCreate(['addon_id'=>$extraShot->id,'item_id'=>$beans->id],['qty_per_unit'=>8]);
        $extraShot->load('recipes.item');
        return view('recipes.index',compact('products','sizes','extraShot'));
    }
}
