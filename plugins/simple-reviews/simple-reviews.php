<?php
/**
 * Plugin Name: Simple Reviews
 * Description: A simple WordPress plugin that registers a custom post type for product reviews and provides REST API support.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Reviews
{
    public function __construct()
    {
        // Register the Custom Post Type (CPT)
        add_action('init', [$this, 'register_product_review_cpt']);

        // Register thes endpoints REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Register the shortcode
        add_shortcode('product_reviews', [$this, 'display_product_reviews']);
    }

    public function register_product_review_cpt()
    {
        register_post_type('product_review', [
            'labels' => [
                'name' => 'Product Reviews',
                'singular_name' => 'Product Review'
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'show_in_rest' => true, // Enable suport to REST API
        ]);
    }

    public function register_rest_routes()
    {
        // Endpoint assign the sentiment
        register_rest_route('mock-api/v1', '/sentiment/', [
            'methods' => 'POST',
            'callback' => [$this, 'analyze_sentiment'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint to get the review history
        register_rest_route('mock-api/v1', '/review-history/', [
            'methods' => 'GET',
            'callback' => [$this, 'get_review_history'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint to get the outliers
        register_rest_route('mock-api/v1', '/outliers/', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sentiment_outliers'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function analyze_sentiment($request)
    {
        $params = $request->get_json_params();
        $text = isset($params['text']) ? sanitize_text_field($params['text']) : '';

        if (empty($text)) {
            return new WP_Error('empty_text', 'No text provided for analysis.', ['status' => 400]);
        }

        $sentiment_scores = ['positive' => 0.9, 'negative' => 0.2, 'neutral' => 0.5];
        $random_sentiment = array_rand($sentiment_scores);
        return rest_ensure_response(['sentiment' => $random_sentiment, 'score' => $sentiment_scores[$random_sentiment]]);
    }
    public function get_review_history()
    {
        $reviews = get_posts([
            'post_type' => 'product_review',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $response = [];
        foreach ($reviews as $review) {
            $response[] = [
                'id' => $review->ID,
                'title' => $review->post_title,
                'sentiment' => get_post_meta($review->ID, 'sentiment', true) ?? 'neutral',
                'score' => get_post_meta($review->ID, 'sentiment_score', true) ?? 0.5,
            ];
        }

        return rest_ensure_response($response);
    }
    public function get_sentiment_outliers()
    {
        $reviews = get_posts([
            'post_type' => 'product_review',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        // Get the score of sentiment
        $scores = [];
        foreach ($reviews as $review) {
            $score = get_post_meta($review->ID, 'sentiment_score', true);
            if ($score !== '') {
                $scores[] = (float) $score;
            }
        }

        // Calculate the average
        $average = array_sum($scores) / count($scores);
        $variance = array_sum(array_map(function ($x) use ($average) {
            return pow($x - $average, 2);
        }, $scores)) / count($scores);

        $std = sqrt($variance);

        // Identify the ouliers
        $outliers = [];
        foreach ($reviews as $review) {
            $score = (float) get_post_meta($review->ID, 'sentiment_score', true);
            if ($score < ($average - $std) || $score > ($average + $std)) {
                $outliers[] = [
                    'id' => $review->ID,
                    'title' => $review->post_title,
                    'sentiment' => get_post_meta($review->ID, 'sentiment', true) ?? 'neutral',
                    'score' => $score,
                ];
            }
        }

        return rest_ensure_response($outliers);
    }

    public function display_product_reviews()
    {
        $reviews = get_posts([
            'post_type' => 'product_review',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $output = '<style>
            .review-positive { color: green; font-weight: bold; }
            .review-negative { color: red; font-weight: bold; }
        </style>';

        $output .= '<ul>';
        foreach ($reviews as $review) {
            $sentiment = get_post_meta($review->ID, 'sentiment', true) ?? 'neutral';
            $class = ($sentiment === 'positive') ? 'review-positive' : (($sentiment === 'negative') ? 'review-negative' : '');
            $output .= "<li class='$class'>{$review->post_title} (Sentiment: $sentiment)</li>";
        }
        $output .= '</ul>';

        return $output;
    }
}

new Simple_Reviews();