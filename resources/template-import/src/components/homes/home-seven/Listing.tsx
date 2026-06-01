/* eslint-disable @typescript-eslint/no-explicit-any */
"use client"
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Navigation } from 'swiper/modules';
import listing_data from "@/data/ListingData"
import Image from "next/image"
import Link from "next/link"
import { useDispatch } from "react-redux"
import { addToWishlist } from "@/redux/features/wishlistSlice"

const setting = {
   spaceBetween: 24,
   loop: true,
   speed: 500,
   autoplay: {
      delay: 4000,
   },
   navigation: {
      nextEl: '.tg-listing-5-slide-next',
      prevEl: '.tg-listing-5-slide-prev',
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
         slidesPerView: 2,
      },
      '0': {
         slidesPerView: 1,
      },
   },
};

const Listing = () => {

   const dispatch = useDispatch();
   // add to wishlist
   const handleAddToWishlist = (item: any) => {
      dispatch(addToWishlist(item));
   };

   return (
      <div className="tg-listing-area pt-135 pb-105 tg-grey-bg">
         <div className="container">
            <div className="row align-items-end">
               <div className="col-lg-9">
                  <div className="tg-location-section-title mb-40">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Our Best Restaurant</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Popular Restaurants in Town</h2>
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
                  <Swiper {...setting} modules={[Autoplay, Navigation]} className="swiper-container tg-listing-slider-2 p-relative fix">
                     {listing_data.filter((items) => items.page === "home_7").map((item) => (
                        <SwiperSlide key={item.id} className="swiper-slide">
                           <div className="tg-listing-card-item tg-listing-5-card-item mb-25">
                              <div className="tg-listing-card-thumb tg-listing-2-card-thumb mb-15 fix p-relative">
                                 <Link href="/tour-details">
                                    <Image className="tg-card-border w-100" src={item.thumb} alt="listing" />
                                    {item.tag && <span className="tg-listing-item-price-discount shape">{item.tag}</span>}
                                    {item.featured && <span className="tg-listing-item-price-discount offer-btm shape-3">
                                       <svg width="12" height="14" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                          <path d="M6.60156 1L0.601562 8.2H6.00156L5.40156 13L11.4016 5.8H6.00156L6.60156 1Z" stroke="white" strokeWidth="0.857143" strokeLinecap="round" strokeLinejoin="round" />
                                       </svg>
                                       {item.featured}
                                    </span>}
                                    {item.recommended && <span className="tg-listing-item-price-discount offer-btm-2 shape-4">{item.recommended}</span>}
                                 </Link>
                                 <div className="tg-listing-item-wishlist">
                                    <a onClick={() => handleAddToWishlist(item)} style={{ cursor: "pointer" }}>
                                       <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                          <path d="M10.5167 16.3416C10.2334 16.4416 9.76675 16.4416 9.48341 16.3416C7.06675 15.5166 1.66675 12.075 1.66675 6.24165C1.66675 3.66665 3.74175 1.58331 6.30008 1.58331C7.81675 1.58331 9.15841 2.31665 10.0001 3.44998C10.8417 2.31665 12.1917 1.58331 13.7001 1.58331C16.2584 1.58331 18.3334 3.66665 18.3334 6.24165C18.3334 12.075 12.9334 15.5166 10.5167 16.3416Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                       </svg>
                                    </a>
                                 </div>
                                 <div className="tg-listing-2-price">
                                    <span>
                                       <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                          <path d="M13.9144 9.57143C14.3877 9.57143 14.7715 9.18764 14.7715 8.71429C14.7715 8.24093 14.3877 7.85714 13.9144 7.85714M13.9144 9.57143H13.0287C12.0818 9.57143 12.2611 10.4286 11.3144 10.4286C10.3675 10.4286 10.5469 9.57143 9.60014 9.57143C8.65321 9.57143 8.83257 10.4286 7.88586 10.4286C6.93893 10.4286 7.11829 9.57143 6.17157 9.57143C5.22464 9.57143 5.404 10.4286 4.45729 10.4286C3.51036 10.4286 3.66121 9.57143 2.71429 9.57143H1.85714M13.9144 9.57143C13.9144 11.4651 12.3507 13 10.4573 13H5.31443C4.28221 13 3.34814 12.544 2.71171 11.8223C2.18071 11.2199 1.85714 10.4329 1.85714 9.57143M1.85714 9.57143C1.38379 9.57143 1 9.18764 1 8.71429C1 8.24093 1.38379 7.85714 1.85714 7.85714M1.85714 7.85714H13.9144M1.85714 7.85714C1.38379 7.85714 1 7.47336 1 7C1 6.52664 1.38379 6.14286 1.85714 6.14286M13.9144 7.85714C14.3877 7.85714 14.7715 7.47336 14.7715 7C14.7715 6.52664 14.3877 6.14286 13.9144 6.14286M1.85714 6.14286H13.9144M1.85714 6.14286C1.85714 4.85843 2.16936 3.85364 2.71171 3.0865C3.76793 1.59207 5.69607 1 7.88586 1C11.1994 1 13.9144 2.35579 13.9144 6.14286" stroke="#560CE3" strokeWidth="0.8" strokeMiterlimit="13.3333" strokeLinecap="round" strokeLinejoin="round" />
                                       </svg>
                                    </span>
                                    <span className="text">{item.country}</span>
                                 </div>
                              </div>
                              <div className="tg-listing-card-content p-relative">
                                 <h4 className="tg-listing-card-title mb-5"><Link href="/tour-details">{item.title}</Link></h4>
                                 <div className="tg-listing-card-review mb-5">
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                                    <span className="tg-listing-rating-percent">({item.total_review} Reviews)</span>
                                 </div>
                                 <span className="tg-listing-card-duration-map d-inline-block mb-10">
                                    <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                       <path d="M12.3329 6.7071C12.3329 11.2324 6.55512 15.1111 6.55512 15.1111C6.55512 15.1111 0.777344 11.2324 0.777344 6.7071C0.777344 5.16402 1.38607 3.68414 2.46962 2.59302C3.55316 1.5019 5.02276 0.888916 6.55512 0.888916C8.08748 0.888916 9.55708 1.5019 10.6406 2.59302C11.7242 3.68414 12.3329 5.16402 12.3329 6.7071Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                       <path d="M6.55512 8.64649C7.61878 8.64649 8.48105 7.7782 8.48105 6.7071C8.48105 5.636 7.61878 4.7677 6.55512 4.7677C5.49146 4.7677 4.6292 5.636 4.6292 6.7071C4.6292 7.7782 5.49146 8.64649 6.55512 8.64649Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                    {item.location}
                                 </span>
                                 <div className="tg-listing-avai d-flex align-items-center justify-content-between">
                                    <Link className="tg-listing-avai-btn" href="/tour-details">View Details</Link>
                                    <div className="tg-listing-card-price d-flex align-items-center">
                                       <span className="form mr-5">From</span>
                                       <span className="price">${item.price}</span>
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

export default Listing
