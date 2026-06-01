/* eslint-disable @typescript-eslint/no-explicit-any */
"use client"
import Button from "@/components/common/Button"
import Image from "next/image"
import Link from "next/link"
import listing_data from "@/data/ListingData"
import { useDispatch } from "react-redux"
import { addToWishlist } from "@/redux/features/wishlistSlice"

import shape_1 from "@/assets/img/banner/banner-2/shape.png"

const Listing = () => {

   const dispatch = useDispatch();
   // add to wishlist
   const handleAddToWishlist = (item: any) => {
      dispatch(addToWishlist(item));
   };

   return (
      <div className="tg-listing-area pt-140 pb-105 p-relative z-index-9">
         <Image className="tg-listing-3-shape d-none d-xl-block" src={shape_1} alt="" />
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
                        <Button text="See All Deal" />
                     </Link>
                  </div>
               </div>
            </div>
            <div className="row">
               {listing_data.filter((items) => items.page === "home_5").map((item) => (
                  <div key={item.id} className="col-xl-3 col-lg-4 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".9s" data-wow-duration=".6s">
                     <div className="tg-listing-card-item tg-listing-3-card-item mb-25">
                        <div className="tg-listing-card-thumb tg-listing-2-card-thumb mb-15 fix p-relative">
                           <Link href="/tour-grid-2">
                              <Image className="tg-card-border w-100" src={item.thumb} alt="listing" />
                              {item.tag && <span className="tg-listing-item-price-discount shape-2">% Offer</span>}
                           </Link>
                           <div className="tg-listing-item-wishlist">
                              <a onClick={() => handleAddToWishlist(item)} style={{ cursor: "pointer" }}>
                                 <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.5167 16.3416C10.2334 16.4416 9.76675 16.4416 9.48341 16.3416C7.06675 15.5166 1.66675 12.075 1.66675 6.24165C1.66675 3.66665 3.74175 1.58331 6.30008 1.58331C7.81675 1.58331 9.15841 2.31665 10.0001 3.44998C10.8417 2.31665 12.1917 1.58331 13.7001 1.58331C16.2584 1.58331 18.3334 3.66665 18.3334 6.24165C18.3334 12.075 12.9334 15.5166 10.5167 16.3416Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                 </svg>
                              </a>
                           </div>
                           <div className="tg-listing-2-price">
                              {item.delete_price && <del>$299</del>}
                              <span className="new">${item.price}</span>
                              <span className="shift">/night</span>
                           </div>
                        </div>
                        <div className="tg-listing-card-content p-relative">
                           <span className="tg-listing-card-duration-map d-inline-block mb-5">
                              <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                 <path d="M12.3329 6.7071C12.3329 11.2324 6.55512 15.1111 6.55512 15.1111C6.55512 15.1111 0.777344 11.2324 0.777344 6.7071C0.777344 5.16402 1.38607 3.68414 2.46962 2.59302C3.55316 1.5019 5.02276 0.888916 6.55512 0.888916C8.08748 0.888916 9.55708 1.5019 10.6406 2.59302C11.7242 3.68414 12.3329 5.16402 12.3329 6.7071Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                                 <path d="M6.55512 8.64649C7.61878 8.64649 8.48105 7.7782 8.48105 6.7071C8.48105 5.636 7.61878 4.7677 6.55512 4.7677C5.49146 4.7677 4.6292 5.636 4.6292 6.7071C4.6292 7.7782 5.49146 8.64649 6.55512 8.64649Z" stroke="currentColor" strokeWidth="1.15556" strokeLinecap="round" strokeLinejoin="round" />
                              </svg>
                              {item.location}
                           </span>
                           <h4 className="tg-listing-card-title mb-0"><Link href="/tour-grid-2">{item.title}</Link></h4>
                           <div className="tg-listing-card-review mb-5">
                              <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span className="tg-listing-rating-icon"><i className="fa-sharp fa-solid fa-star"></i></span>
                              <span className="tg-listing-rating-percent">(5 Reviews)</span>
                           </div>
                           <div className="tg-listing-card-duration-tour">
                              <span className="tg-listing-card-duration-time">
                                 <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8.00175 3.73329V7.99996L10.8462 9.42218M15.1128 8.00003C15.1128 11.9274 11.9291 15.1111 8.00174 15.1111C4.07438 15.1111 0.890625 11.9274 0.890625 8.00003C0.890625 4.07267 4.07438 0.888916 8.00174 0.888916C11.9291 0.888916 15.1128 4.07267 15.1128 8.00003Z" stroke="currentColor" strokeWidth="1.06667" strokeLinecap="round" strokeLinejoin="round" />
                                 </svg>
                                 {item.time}
                              </span>
                           </div>
                        </div>
                     </div>
                  </div>
               ))}
            </div>
         </div>
      </div>
   )
}

export default Listing
