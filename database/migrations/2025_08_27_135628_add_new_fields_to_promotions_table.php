<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('affiliate_url')->nullable()->after('url');
            $table->decimal('original_price', 10, 2)->nullable()->after('image');
            $table->decimal('discounted_price', 10, 2)->nullable()->after('original_price');
            $table->integer('discount_percentage')->nullable()->after('discounted_price');
            $table->string('store')->nullable()->after('discount_percentage');
            $table->string('category')->nullable()->after('store');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'posted'])->default('draft')->after('category');
            $table->text('gemini_generated_post')->nullable()->after('status');
            $table->string('source_url')->nullable()->after('gemini_generated_post');
            $table->timestamp('posted_at')->nullable()->after('source_url');
            $table->boolean('is_approved')->default(false)->after('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'affiliate_url',
                'original_price',
                'discounted_price',
                'discount_percentage',
                'store',
                'category',
                'status',
                'gemini_generated_post',
                'source_url',
                'posted_at',
                'is_approved'
            ]);
        });
    }
};
