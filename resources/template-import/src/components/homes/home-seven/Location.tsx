"use client"
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Navigation } from 'swiper/modules';
import Image from "next/image"
import location_data from "@/data/LocationData"
import Link from "next/link"

import shape_1 from "@/assets/img/location/shape-2.png"

const setting = {
   slidesPerView: 4,
   loop: true,
   spaceBetween: 30,
   autoplay: {
      delay: 5000,
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
      <div className="tg-location-area pt-135 pb-130 tg-grey-bg p-relative z-index-1">
         <Image className="tg-location-shape d-none d-xl-block" src={shape_1} alt="" />
         <div className="container">
            <div className="row align-items-end">
               <div className="col-lg-9">
                  <div className="tg-location-section-title mb-40">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Next Adventure Destination</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Popular Travel Destinations <br /> Available Worldwide</h2>
                  </div>
               </div>
               <div className="col-lg-3">
                  <div className="tg-listing-5-slider-navigation text-end mb-50 wow fadeInUp" data-wow-delay=".4s" data-wow-duration="1s">
                     <button className="tg-listing-5-slide-prev"><i className="fa-solid fa-arrow-left-long"></i></button>
                     <button className="tg-listing-5-slide-next"><i className="fa-solid fa-arrow-right-long"></i></button>
                  </div>
               </div>
            </div>
            <div className="row">
               <div className="col-12">
                  <Swiper {...setting} modules={[Autoplay, Navigation]} className="swiper-container tg-location-5-slider p-relative fix">
                     {location_data.filter((items) => items.page === "home_7_2").map((item) => (
                        <SwiperSlide key={item.id} className="swiper-slide">
                           <div className="tg-location-5-wrap p-relative">
                              <div className="tg-location-5-thumb bg-white p-relative rounded-circale">
                                 <Image className="w-100" src={item.thumb} alt="location" />
                                 <span className="tg-location-5-bottom-bg"></span>
                                 <div className="tg-location-5-inner p-absolute">
                                    <div className="tg-location-5-content text-center">
                                       <h4 className="mb-0 lh-1"><Link href="/map-listing">{item.title}</Link></h4>
                                       <span>{item.total} Restaurants</span>
                                    </div>
                                 </div>
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
