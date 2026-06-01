"use client"
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay } from 'swiper/modules';
import Image from "next/image";
import testi_data from '@/data/TestimonialData';

const setting = {
   spaceBetween: 25,
   loop: true,
   speed: 500,
   autoplay: {
      delay: 4000,
   },
   pagination: false,
   navigation: false,
   breakpoints: {
      '1200': {
         slidesPerView: 3,
      },
      '992': {
         slidesPerView: 2,
      },
      '768': {
         slidesPerView: 2,
      },
      '576': {
         slidesPerView: 1,
      },
      '0': {
         slidesPerView: 1,
      },
   },
};

const Testimonial = () => {
   return (
      <div className="tg-testimonial-area pt-105 pb-100">
         <div className="container">
            <div className="row">
               <div className="col-lg-12">
                  <div className="tg-location-section-title text-center mb-30">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Clients Feedback About Us</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">See Those Lovely Words From Clients</h2>
                     <p className="text-none wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".9s">Are you tired of the typical tourist destinations and looking<br />
                        to step out of your comfort zonetravel</p>
                  </div>
               </div>
               <Swiper {...setting} modules={[Autoplay]} className="swiper-container tg-testimonial-slider fix">
                  {testi_data.map((item) => (
                     <SwiperSlide key={item.id} className="swiper-slide">
                        <div className="tg-testimonial-item mb-30">
                           <div className="tg-testimonial-avatar-top d-flex align-items-start justify-content-between">
                              <div className="tg-testimonial-avatar-inner d-flex align-items-center mr-20 mb-20">
                                 <div className="tg-testimonial-avatar-thumb mr-15">
                                    <Image className="rounded-circale" src={item.avatar} alt="avatar" />
                                 </div>
                                 <div className="tg-testimonial-avatar-content">
                                    <h5>{item.name}</h5>
                                    <span>{item.designation}</span>
                                 </div>
                              </div>
                              <div className="tg-testimonial-avatar-qoute">
                                 <span>
                                    <svg width="44" height="34" viewBox="0 0 44 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                       <path d="M1.23438 33.5V28.4177L2.53846 28.1579L2.53874 28.1578C5.54288 27.5574 7.72911 26.3475 8.91074 24.4843L8.91078 24.4843L8.91406 24.479C9.51081 23.5115 9.85009 22.4074 9.89965 21.2718L9.92242 20.75H9.40013H2.85938C2.4284 20.75 2.01507 20.5788 1.71033 20.274C1.40558 19.9693 1.23438 19.556 1.23438 19.125V4.25C1.23438 2.18227 2.91664 0.5 4.98438 0.5H17.7344C18.1654 0.5 18.5787 0.671205 18.8834 0.975951C19.1882 1.2807 19.3594 1.69402 19.3594 2.125V19.125V19.1745L19.364 19.1976C19.3646 19.2056 19.3653 19.2163 19.3661 19.2296C19.3684 19.2694 19.3713 19.3294 19.3734 19.4081C19.3776 19.5653 19.3788 19.795 19.3678 20.0841C19.3458 20.6626 19.275 21.4756 19.0821 22.419C18.696 24.3079 17.8253 26.7003 15.8985 28.7905L1.23438 33.5ZM1.23438 33.5H2.85938C8.68388 33.5 13.0411 31.8881 15.8984 28.7906L1.23438 33.5ZM24.6243 33.5V28.4177L25.9283 28.1579L25.9286 28.1578C28.9328 27.5574 31.119 26.3475 32.3006 24.4843L32.3007 24.4843L32.3039 24.479C32.9007 23.5115 33.24 22.4074 33.2895 21.2718L33.3123 20.75H32.79H26.2493C25.8183 20.75 25.4049 20.5788 25.1002 20.274C24.7955 19.9693 24.6243 19.556 24.6243 19.125V4.25C24.6243 2.18227 26.3065 0.5 28.3743 0.5H41.1242C41.5552 0.5 41.9686 0.671206 42.2733 0.975951C42.578 1.2807 42.7492 1.69402 42.7492 2.125V19.125V19.1745L42.7538 19.1975C42.7544 19.2056 42.7551 19.2162 42.7559 19.2294C42.7583 19.2692 42.7611 19.3292 42.7631 19.4079C42.7673 19.5651 42.7685 19.7949 42.7574 20.084C42.7353 20.6625 42.6642 21.4755 42.4712 22.419C42.0848 24.3079 41.2141 26.7003 39.2884 28.7905C36.4311 31.8881 32.0738 33.5 26.2493 33.5H24.6243ZM19.3623 19.1774C19.3622 19.176 19.3623 19.1773 19.3628 19.1823C19.3625 19.1799 19.3623 19.1782 19.3623 19.1774Z" stroke="#D1D1D1" />
                                    </svg>
                                 </span>
                              </div>
                           </div>
                           <p className="tg-testimonial-avatar-para mb-10">{item.desc}</p>
                           <div className="tg-testimonial-ratings">
                              <span><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span><i className="fa-sharp fa-solid fa-star"></i></span>
                           </div>
                        </div>
                     </SwiperSlide>
                  ))}
               </Swiper>
            </div>
         </div>
      </div>
   )
}

export default Testimonial
