"use client"
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Pagination } from 'swiper/modules';
import Image from 'next/image'
import Link from 'next/link'
import Button from '@/components/common/Button'
import listing_data from '@/data/ListingData'

import shape from '@/assets/img/banner/banner-2/shape.png'

const setting = {
   spaceBetween: 24,
   loop: true,
   speed: 500,
   autoplay: {
      delay: 4000,
   },
   pagination: {
      el: ".swiper-pagination",
   },
   breakpoints: {
      '1200': {
         slidesPerView: 4,
      },
      '992': {
         slidesPerView: 3,
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

const Listing = () => {
   return (
      <div className="tg-listing-area pt-90 pb-115 p-relative z-index-9">
         <Image className="tg-listing-3-shape tg-listing-4-shape d-none d-xl-block" src={shape} alt="" />
         <div className="container">
            <div className="row align-items-end">
               <div className="col-lg-9">
                  <div className="tg-location-section-title mb-40">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Most Popular Tour Packages </h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Our Popular Tours</h2>
                  </div>
               </div>
               <div className="col-lg-3">
                  <div className="tg-location-3-btn text-end wow fadeInUp mb-40" data-wow-delay=".6s" data-wow-duration=".9s">
                     <Link href="/tour-grid-1" className="tg-btn tg-btn-gray tg-btn-switch-animation">
                        <Button text='See All Deal' />
                     </Link>
                  </div>
               </div>
            </div>
            <div className="row">
               <div className="col-12">
                  <Swiper {...setting} modules={[Autoplay, Pagination]} wrapperClass="mb-35" className="swiper-container tg-listing-slider p-relative fix">
                     {listing_data.filter((items) => items.page === "home_5").slice(0, 5).map((item) => (
                        <SwiperSlide key={item.id} className="swiper-slide">
                           <div className="tg-listing-card-item tg-listing-4-card-item mb-25">
                              <div className="tg-listing-card-thumb tg-listing-2-card-thumb mb-15 fix p-relative">
                                 <Link href="#">
                                    <Image className="tg-card-border w-100" src={item.thumb} alt="listing" />
                                    {item.tag && <span className="tg-listing-item-price-discount shape">{item.tag}</span>}
                                 </Link>
                                 <div className="tg-listing-2-price">
                                    <span className="new">${item.price}</span>
                                    <span className="shift">/night</span>
                                 </div>
                              </div>
                              <div className="tg-listing-card-content p-relative">
                                 <h4 className="tg-listing-card-title mb-5"><Link href="#">{item.title}</Link></h4>
                                 <span className="tg-listing-card-duration-map d-inline-block">
                                    <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                       <path d="M12.3329 6.7071C12.3329 11.2324 6.55512 15.1111 6.55512 15.1111C6.55512 15.1111 0.777344 11.2324 0.777344 6.7071C0.777344 5.16402 1.38607 3.68414 2.46962 2.59302C3.55316 1.5019 5.02276 0.888916 6.55512 0.888916C8.08748 0.888916 9.55708 1.5019 10.6406 2.59302C11.7242 3.68414 12.3329 5.16402 12.3329 6.7071Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                       <path d="M6.55512 8.64649C7.61878 8.64649 8.48105 7.7782 8.48105 6.7071C8.48105 5.636 7.61878 4.7677 6.55512 4.7677C5.49146 4.7677 4.6292 5.636 4.6292 6.7071C4.6292 7.7782 5.49146 8.64649 6.55512 8.64649Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                    {item.location}
                                 </span>
                                 <div className="tg-listing-card-review mb-10">
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-percent">({item.total_review} Reviews)</span>
                                 </div>
                                 <div className="tg-listing-avai d-flex align-items-center justify-content-between">
                                    <Link className="tg-listing-avai-btn" href="#">Check Availability</Link>
                                    <div className="tg-listing-item-wishlist">
                                       <Link href="#">
                                          <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path d="M10.5167 16.3416C10.2334 16.4416 9.76675 16.4416 9.48341 16.3416C7.06675 15.5166 1.66675 12.075 1.66675 6.24165C1.66675 3.66665 3.74175 1.58331 6.30008 1.58331C7.81675 1.58331 9.15841 2.31665 10.0001 3.44998C10.8417 2.31665 12.1917 1.58331 13.7001 1.58331C16.2584 1.58331 18.3334 3.66665 18.3334 6.24165C18.3334 12.075 12.9334 15.5166 10.5167 16.3416Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                          </svg>
                                       </Link>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </SwiperSlide>
                     ))}
                     <div className="tg-listing-4-pagination swiper-pagination"></div>
                  </Swiper>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Listing
