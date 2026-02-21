<?php
$pageTitle = 'About Us';
$currentPage = 'about';
require_once 'includes/header.php';
?>

<style>
    .section-title {
        position: relative;
        display: inline-block;
        margin-bottom: 2rem;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--gradient-secondary);
        border-radius: 2px;
    }
    
    .about-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 2rem;
    }
    
    .about-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    
    .counter {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 2rem;
        margin: 3rem 0;
    }
</style>

<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="section-title text-center mb-5">About Us</h1>
                
                <div class="about-content">
                    <div class="about-card">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <h2 class="fw-bold mb-3">Our Story</h2>
                                <p class="lead">Founded in 2015, Curtisyn has grown from a small family business into a trusted name in home decor.</p>
                                <p>We believe that the right window treatments can transform any space into a sanctuary of comfort and style. Our journey began with a simple vision: to provide beautiful, high-quality curtains and home accessories that bring warmth and elegance to every home.</p>
                            </div>
                            <div class="col-md-6">
                                <div style="height: 300px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-home text-white" style="font-size: 4rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="about-card">
                        <div class="row align-items-center">
                            <div class="col-md-6 order-md-2">
                                <h2 class="fw-bold mb-3">Our Mission</h2>
                                <p class="lead">We are dedicated to helping homeowners create beautiful, functional living spaces.</p>
                                <p>Every curtain we offer is carefully selected for its quality, durability, and aesthetic appeal. Our mission is to provide exceptional products and services that exceed our customers' expectations while maintaining competitive prices.</p>
                            </div>
                            <div class="col-md-6 order-md-1">
                                <div style="height: 300px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-bullseye text-white" style="font-size: 4rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="counter text-center">
                        <div class="row">
                            <div class="col-md-3 col-6 mb-4 mb-md-0">
                                <h3 class="display-4 fw-bold text-primary">5000+</h3>
                                <p class="mb-0">Happy Customers</p>
                            </div>
                            <div class="col-md-3 col-6 mb-4 mb-md-0">
                                <h3 class="display-4 fw-bold text-primary">500+</h3>
                                <p class="mb-0">Products Sold</p>
                            </div>
                            <div class="col-md-3 col-6 mb-4 mb-md-0">
                                <h3 class="display-4 fw-bold text-primary">8</h3>
                                <p class="mb-0">Years Experience</p>
                            </div>
                            <div class="col-md-3 col-6">
                                <h3 class="display-4 fw-bold text-primary">24/7</h3>
                                <p class="mb-0">Support</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="about-section">
                        <h2 class="section-title mb-4">Why Choose Us</h2>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="about-card h-100">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-star fs-2 text-warning me-3 mt-1"></i>
                                        <div>
                                            <h3 class="fw-bold">Premium Quality</h3>
                                            <p class="mb-0">All our products are made from high-grade materials built to last. We source only the finest fabrics and components for superior durability.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="about-card h-100">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-headset fs-2 text-primary me-3 mt-1"></i>
                                        <div>
                                            <h3 class="fw-bold">Expert Support</h3>
                                            <p class="mb-0">Our team provides personalized guidance to help you find the perfect fit. We offer professional advice and consultation services.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="about-card h-100">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-shipping-fast fs-2 text-success me-3 mt-1"></i>
                                        <div>
                                            <h3 class="fw-bold">Fast Shipping</h3>
                                            <p class="mb-0">Quick and reliable delivery to get your curtains to you when you need them. Same-day and next-day delivery options available.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="about-card h-100">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-shield-alt fs-2 text-info me-3 mt-1"></i>
                                        <div>
                                            <h3 class="fw-bold">Satisfaction Guaranteed</h3>
                                            <p class="mb-0">We stand behind every product with our hassle-free return policy. Your satisfaction is our top priority.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

