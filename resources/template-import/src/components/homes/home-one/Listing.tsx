/* eslint-disable @typescript-eslint/no-explicit-any */
"use client"
import Image from "next/image"
import listing_data from "@/data/ListingData"
import Link from "next/link"
import Wishlist from "@/svg/home-one/Wishlist"
import Clock from "@/svg/home-one/Clock"
import User from "@/svg/home-one/User"
import Location from "@/svg/home-one/Location"
import { useDispatch } from "react-redux"
import { addToWishlist } from "@/redux/features/wishlistSlice"

import shape_1 from "@/assets/img/listing/su/shape-2.png"
import shape_2 from "@/assets/img/listing/su/shape-1.png"

const Listing = () => {

   const dispatch = useDispatch();
   // add to wishlist
   const handleAddToWishlist = (item: any) => {
      dispatch(addToWishlist(item));
   };

   return (
      <div className="tg-listing-area tg-listing-su-spacing tg-grey-bg-2 pt-120 p-relative">
         <Image className="tg-listing-su-shape d-none d-xl-block" src={shape_1} alt="" />
         <Image className="tg-listing-su-shape-2 d-none d-xxl-block" src={shape_2} alt="" />
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-6">
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
                                 <Wishlist />
                              </a>
                           </div>
                        </div>
                        <div className="tg-listing-card-content">
                           <div className="tg-listing-card-duration-tour d-flex align-items-center gap-3">
                              <span className="tg-listing-card-duration-map mb-5">
                                 <Clock />
                                 {item.time}
                              </span>
                              <span className="tg-listing-card-duration-time mb-5">
                                 <User />
                                 {item.guest}
                              </span>
                           </div>
                           <h4 className="tg-listing-card-title mb-10"><Link href="tour-details.html">{item.title}</Link></h4>
                           <div className="tg-listing-card-duration-tour mb-20">
                              <span className="tg-listing-card-duration-map">
                                 <Location />
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
