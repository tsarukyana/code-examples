<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categories extends Model
{
    use SoftDeletes;
    protected $table = 'categories';


    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $appends = ['order_label', 'created_formated'];

    public function getCreatedFormatedAttribute()
    {
        if ($this->created_at != "") {
            return \Carbon\Carbon::parse($this->created_at);
        }
        return $this->created_at;
    }

    public function getOrderLabelAttribute()
    {
        if($this->parent){
            return $this->parent->display_order.".".$this->display_order;
        }else{
            return $this->display_order;
        }
    }

    public function next()
    {
        if($this->parent){
            $cat = Categories::where("display_order",">",$this->display_order)->where("parent_id",$this->parent_id)->orderby("display_order","asc")->first();
            if($cat){
                return $cat;
            }
        }

        return null;
    }
    public function previous()
    {
        if($this->parent){
            $cat = Categories::where("display_order","<",$this->display_order)->where("parent_id",$this->parent_id)->orderby("display_order","DESC")->first();
            if($cat){
                return $cat;
            }
        }

        return null;
    }

    public function child()
    {
        return $this->hasMany('App\Models\Categories', 'parent_id', 'id')->select(['id','name','slug','parent_id','display_order'])->with('refefile')->orderby('display_order','asc');
    }
    public function parent()
    {
        return $this->belongsTo('App\Models\Categories', 'parent_id', 'id');
    }
    public function scopeParentonly($query)
    {
        return $query->where('parent_id', 0);
    }
    public function scopeChildonly($query)
    {
        return $query->where('parent_id','>',0);
    }
    public function refefile()
    {
        return $this->hasMany('App\Models\Refefile', 'refe_field_id', 'id')->where('refe_table_field_name', 'cat_id');
    }

    public function topics() {
        return $this->hasMany(Topics::class, 'category_id', 'id')->where('status', 'active');
    }

    public function producedtopics(){
        return $this->hasMany('App\Models\Craft', 'category_id', 'id');
    }
    public function getProducedcontent(){
        $craft_ids = $this->producedtopics->pluck("id");
        return Refefile::whereIn('refe_field_id',$craft_ids)->where('refe_table_field_name', 'craft_id')->orderBy("created_at")->get();
    }
    public function publishedcontent(){
        return $this->hasMany('App\Models\PublishedContent', 'category_id', 'id');
    }

    public function getTopics(){
        return $this->hasMany('App\Models\Topics', 'category_id', 'id');
    }

    public function craft(){
        return $this->hasMany('App\Models\Craft', 'category_id', 'id')->where('created_by', \Auth::user()->id)->where('status','active');
    }

    public function getCraft(){
        return $this->hasMany('App\Models\Craft', 'category_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function planedTopics(): HasMany
    {
        return $this->hasMany('App\Models\Topics', 'category_id', 'id')->where('status','planning')->orderBy('order', 'asc');
    }

    /**
     * @return HasMany
     */
    public function craftTopics(): HasMany
    {
        return $this->hasMany(Craft::class, 'category_id', 'id')->where('status','active');
    }
}
