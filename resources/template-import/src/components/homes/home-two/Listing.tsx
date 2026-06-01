/* eslint-disable @typescript-eslint/no-explicit-any */
"use client"
import Image from "next/image"
import listing_data from "@/data/ListingData"
import Link from "next/link"
import { useDispatch } from "react-redux"
import { addToWishlist } from "@/redux/features/wishlistSlice"

import shape_1 from "@/assets/img/listing/su/shape-3.png"

const Listing = () => {

   const dispatch = useDispatch();
   // add to wishlist
   const handleAddToWishlist = (item: any) => {
      dispatch(addToWishlist(item));
   };

   return (
      <div className="tg-listing-area pt-120 pb-120 p-relative">
         <Image className="tg-listing-su-2-shape p-absolute d-none d-xxl-block" src={shape_1} alt="" />
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-6 col-md-8">
                  <div className="tg-listing-section-title-wrap text-center mb-40">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Explore the world</h5>
                     <h2 className="tg-section-su-title text-none wow fadeInUp mb-15" data-wow-delay=".5s" data-wow-duration=".9s">Our Amazing Featured Tour  Package The World</h2>
                  </div>
               </div>
            </div>
            <div className="row">
               {listing_data.filter((items) => items.page === "home_1").map((item) => (
                  <div key={item.id} className="col-xl-4 col-lg-4 col-md-6">
                     <div className="tg-listing-card-item tg-listing-su-card-item mb-25">
                        <div className="tg-listing-card-thumb fix mb-25 p-relative">
                           <Link href="/tour-details">
                              <Image className="tg-card-border w-100" src={item.thumb} alt="listing" />
                              {item.tag && <span className="tg-listing-item-price-discount">{item.tag}</span>}
                           </Link>
                           <div className="tg-listing-item-wishlist">
                              <a onClick={() => handleAddToWishlist(item)} style={{ cursor: "pointer" }}>
                                 <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.5167 16.3416C10.2334 16.4416 9.76675 16.4416 9.48341 16.3416C7.06675 15.5166 1.66675 12.075 1.66675 6.24165C1.66675 3.66665 3.74175 1.58331 6.30008 1.58331C7.81675 1.58331 9.15841 2.31665 10.0001 3.44998C10.8417 2.31665 12.1917 1.58331 13.7001 1.58331C16.2584 1.58331 18.3334 3.66665 18.3334 6.24165C18.3334 12.075 12.9334 15.5166 10.5167 16.3416Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                 </svg>
                              </a>
                           </div>
                        </div>
                        <div className="tg-listing-card-content">
                           <div className="tg-listing-card-duration-tour d-flex align-items-center gap-3">
                              <span className="tg-listing-card-duration-map mb-5">
                                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clipPath="url(#clip0_16_2737)">
                                       <path d="M7.99979 3.73329V7.99996L10.8442 9.42218M15.1109 8.00003C15.1109 11.9274 11.9271 15.1111 7.99978 15.1111C4.07242 15.1111 0.888672 11.9274 0.888672 8.00003C0.888672 4.07267 4.07242 0.888916 7.99978 0.888916C11.9271 0.888916 15.1109 4.07267 15.1109 8.00003Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                    </g>
                                    <defs>
                                       <clipPath id="clip0_16_2737">
                                          <rect width="16" height="16" fill="white" />
                                       </clipPath>
                                    </defs>
                                 </svg>
                                 {item.time}
                              </span>
                              <span className="tg-listing-card-duration-time mb-5">
                                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.61756 14.0445C1.40423 14.0445 1.19978 13.9378 1.09312 13.8311L1.00423 13.68C0.950894 13.5822 0.888672 13.4756 0.888672 13.3156C0.888672 12.2222 1.19978 11.0756 1.77756 9.99114C2.31978 9.06669 3.13756 8.22225 4.04423 7.65336C3.68867 7.18225 3.41312 6.59558 3.27089 6.00892C3.20867 5.55558 3.14645 4.72003 3.34201 4.0178C3.53756 3.24447 4.04423 2.55114 4.31978 2.21336C4.71089 1.82225 5.31534 1.32447 5.99089 1.12892C6.47978 0.968916 6.97756 0.888916 7.46645 0.888916H8.01756C8.71978 0.977805 9.37756 1.23558 9.93756 1.63558C10.4798 2.02669 10.8976 2.48892 11.2531 3.11114C11.582 3.68892 11.7509 4.35558 11.7509 5.09336C11.7509 6.05336 11.4487 6.96003 10.8887 7.66225C11.3064 7.9378 11.7331 8.24003 12.1598 8.58669C12.8798 9.30669 13.2887 10.0267 13.6264 10.6934C13.9642 11.5289 14.1153 12.3467 14.1153 13.2267C14.1153 13.44 14.0087 13.6445 13.902 13.7511C13.7953 13.8578 13.5998 13.9645 13.3776 13.9645C13.2976 13.9645 13.182 13.9645 13.0664 13.8756C12.9509 13.84 12.8531 13.7511 12.8265 13.6356L12.6576 13.4667V13.3956C12.6131 13.3067 12.5776 13.2445 12.5776 13.1556C12.5776 12.5422 12.462 11.9467 12.1953 11.2445C11.9731 10.64 11.5909 10.1067 11.0576 9.65336C10.6042 9.28003 10.1776 8.92447 9.68867 8.69336C9.00423 9.10225 8.27534 9.30669 7.46645 9.30669C6.69312 9.30669 5.90201 9.09336 5.24423 8.70225C4.39089 9.10225 3.67978 9.71558 3.19089 10.4889C2.63089 11.3689 2.34645 12.2934 2.34645 13.2356C2.34645 13.4489 2.23978 13.6534 2.13312 13.76C2.07089 13.92 1.85756 14.0445 1.61756 14.0445ZM6.94201 7.84003C7.00423 7.84003 7.11089 7.8578 7.21756 7.88447C7.30645 7.90225 7.38645 7.92003 7.45756 7.92003C7.83978 7.92003 8.20423 7.84003 8.48867 7.6978C9.03089 7.46669 9.39534 7.16447 9.76867 6.64892C10.0531 6.21336 10.2131 5.70669 10.2131 5.17336C10.2131 4.44447 9.92867 3.77781 9.39534 3.24447C8.90645 2.69336 8.28423 2.42669 7.46645 2.42669C6.93312 2.42669 6.41756 2.5778 5.98201 2.87114C5.43089 3.19114 5.13756 3.68003 4.94201 4.07114C4.70201 4.62225 4.65756 5.1378 4.79089 5.68892C4.86201 6.18669 5.13756 6.71114 5.53756 7.11114C5.92867 7.50225 6.45312 7.77781 6.94201 7.84003Z" fill="currentColor" />
                                 </svg>
                                 {item.guest}
                              </span>
                           </div>
                           <h4 className="tg-listing-card-title mb-10"><Link href="tour-details.html">{item.title}</Link></h4>
                           <div className="tg-listing-card-duration-tour mb-20">
                              <span className="tg-listing-card-duration-map">
                                 <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.3329 6.7071C12.3329 11.2324 6.55512 15.1111 6.55512 15.1111C6.55512 15.1111 0.777344 11.2324 0.777344 6.7071C0.777344 5.16402 1.38607 3.68414 2.46962 2.59302C3.55316 1.5019 5.02276 0.888916 6.55512 0.888916C8.08748 0.888916 9.55708 1.5019 10.6406 2.59302C11.7242 3.68414 12.3329 5.16402 12.3329 6.7071Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                    <path d="M6.55512 8.64649C7.61878 8.64649 8.48105 7.7782 8.48105 6.7071C8.48105 5.636 7.61878 4.7677 6.55512 4.7677C5.49146 4.7677 4.6292 5.636 4.6292 6.7071C4.6292 7.7782 5.49146 8.64649 6.55512 8.64649Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                 </svg>
                                 {item.location}
                              </span>
                           </div>
                           <div className="tg-listing-card-price d-flex align-items-end justify-content-between">
                              <div>
                                 <span className="tg-listing-card-currency-amount d-flex align-items-center">
                                    <span className="currency-symbol mr-5">From</span>${item.price}
                                 </span>
                              </div>
                              <div>
                                 <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i> {item.review}</span>
                                 <span className="tg-listing-rating-percent">{item.total_review}</span>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               ))}
               <div className="col-12">
                  <div className="text-center mt-15">
                     <Link href="/tour-grid-1" className="tg-btn tg-btn-transparent tg-btn-su-transparent">See More Tours</Link>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Listing
