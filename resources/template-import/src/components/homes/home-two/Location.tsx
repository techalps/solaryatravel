import location_data from "@/data/LocationData"
import Image from "next/image"
import Link from "next/link"

const Location = () => {
   return (
      <div className="tg-location-area tg-location-su-2-wrap fix pt-120 pb-90 p-relative">
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-6">
                  <div className="tg-location-section-title-wrap text-center mb-40">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-15 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Best Places near at you</h5>
                     <h2 className="tg-section-su-title text-none wow fadeInUp mb-15" data-wow-delay=".5s" data-wow-duration=".9s">Explore Top Destinations</h2>
                  </div>
               </div>
            </div>
            <div className="row gx-30">
               {location_data.filter((items) => items.page === "home_2").map((item) => (
                  <div key={item.id} className={`${item.class} col-lg-4 col-md-6 mb-30`}>
                     <div className="tg-location-3-wrap tg-location-su-wrap p-relative tg-round-25 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".9s">
                        <div className="tg-location-thumb tg-round-25">
                           <Image className="w-100 tg-round-25" src={item.thumb} alt="location" />
                        </div>
                        <div className="tg-location-content tg-location-su-content">
                           <div className="content">
                              <h3 className="tg-location-title mb-5"><Link href="/tour-grid-1">{item.title}</Link></h3>
                           </div>
                           <Link className="icons" href="/tour-grid-1">
                              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                 <path d="M2 13.0969L13.0969 2M13.0969 2H2M13.0969 2V13.0969" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                              </svg>
                           </Link>
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
