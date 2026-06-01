"use client"
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Navigation } from 'swiper/modules';
import Image from "next/image";
import Link from "next/link";
import location_data from '@/data/LocationData';

import location_bg from "@/assets/img/destination/tu/bg.png"

const setting = {
   slidesPerView: 4,
   loop: true,
   spaceBetween: 30,
   autoplay: {
      delay: 3000,
      disableOnInteraction: false,
   },
   pagination: false,
   navigation: {
      prevEl: ".tg-listing-5-slide-prev",
      nextEl: ".tg-listing-5-slide-next",
   },
   breakpoints: {
      '1400': {
         slidesPerView: 4,
      },
      '1200': {
         slidesPerView: 3,
      },
      '768': {
         slidesPerView: 2,
      },
      '0': {
         slidesPerView: 1,
      },
   },
};

const Location = () => {
   return (
      <div className="tg-location-area p-relative z-index-1 pb-65 pt-120">
         <div className="tg-location-su-bg">
            <Image src={location_bg} alt="" />
         </div>
         <div className="container">
            <div className="row align-items-center">
               <div className="col-lg-9">
                  <div className="tg-location-section-title mb-30">
                     <h5 className="tg-section-su-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Best Places near at you</h5>
                     <h2 className="tg-section-su-title text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Explore Top Destinations</h2>
                  </div>
               </div>
               <div className="col-lg-3">
                  <div className="tg-listing-5-slider-navigation tg-location-su-slider-navigation text-end mb-30 wow fadeInUp" data-wow-delay=".4s" data-wow-duration="1s">
                     <button className="tg-listing-5-slide-prev"><i className="fa-solid fa-arrow-left-long"></i></button>
                     <button className="tg-listing-5-slide-next"><i className="fa-solid fa-arrow-right-long"></i></button>
                  </div>
               </div>
            </div>
            <div className="row">
               <div className="col-12">
                  <Swiper {...setting} modules={[Autoplay, Navigation]} className="swiper-container tg-location-su-slider">
                     {location_data.filter((items) => items.page === "home_1").map((item) => (
                        <SwiperSlide key={item.id} className="swiper-slide">
                           <div className="tg-location-3-wrap  tg-location-su-wrap  p-relative mb-30 tg-round-25">
                              <div className="tg-location-thumb tg-round-25">
                                 <Image className="w-100 tg-round-25" src={item.thumb} alt="location" />
                              </div>
                              <div className="tg-location-content tg-location-su-content">
                                 <div className="content">
                                    <h3 className="tg-location-title mb-5"><Link href="/tour-grid-1">{item.title}</Link></h3>
                                    <span className="tg-location-su-duration">{item.total}</span>
                                 </div>
                                 <Link className="icons" href="/tour-grid-1">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                       <path d="M2 13.0969L13.0969 2M13.0969 2H2M13.0969 2V13.0969" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                 </Link>
                              </div>
                           </div>
                        </SwiperSlide>
                     ))}
                  </Swiper>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Location
