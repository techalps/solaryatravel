import Image from 'next/image'
import Link from 'next/link'
import Button from '@/components/common/Button'
import location_data from '@/data/LocationData'

import location_1 from "@/assets/img/location/shape-2.png"

const Location = () => {
   return (
      <div className="tg-location-area p-relative pb-60 pt-140">
         <Image className="tg-location-shape tg-location-4-shape d-none d-lg-block" src={location_1} alt="shape" />
         <div className="container">
            <div className="row align-items-end">
               <div className="col-lg-9">
                  <div className="tg-location-section-title mb-40">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Next Adventure Destination</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Popular Travel Destinations <br />Available Worldwide</h2>
                  </div>
               </div>
               <div className="col-lg-3">
                  <div className="tg-location-3-btn text-end wow fadeInUp mb-40" data-wow-delay=".6s" data-wow-duration=".9s">
                     <Link href="/map-listing" className="tg-btn tg-btn-gray tg-btn-switch-animation">
                        <Button text='All Locations' />
                     </Link>
                  </div>
               </div>
            </div>
            <div className="row">
               {location_data.filter((items) => items.page === "home_6").map((item) => (
                  <div key={item.id} className="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".9s">
                     <div className="bg-white tg-round-25 p-relative z-index-1">
                        <div className="tg-location-wrap p-relative mb-30">
                           <div className="tg-location-thumb">
                              <Image className="w-100" src={item.thumb} alt="location" />
                           </div>
                           <div className="tg-location-content text-center">
                              <span className="tg-location-time">{item.total}</span>
                              <h3 className="tg-location-title mb-0"><Link href="/map-listing">{item.title}</Link></h3>
                           </div>
                           <div className="tg-location-border one"></div>
                           <div className="tg-location-border two"></div>
                        </div>
                     </div>
                  </div>
               ))}
            </div>
         </div>
      </div>
   )
}

export default Location
