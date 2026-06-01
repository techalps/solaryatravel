import Image, { StaticImageData } from "next/image";

import thumb_1 from "@/assets/img/destination/des.jpg"
import thumb_2 from "@/assets/img/destination/des-2.jpg"
import thumb_3 from "@/assets/img/destination/des-3.jpg"
import thumb_4 from "@/assets/img/destination/des-4.jpg"
import shape from "@/assets/img/listing/listing-2/shape.png"
import Link from "next/link";

interface DataType {
   id: number;
   thumb: StaticImageData;
   country: string;
   tag: string[];
}

const destination_data: DataType[] = [
   {
      id: 1,
      thumb: thumb_1,
      country: "",
      tag: ["01 Tour", "02 Hotel", "04 Acitivity", "01 Restaurant"],
   },
   {
      id: 2,
      thumb: thumb_2,
      country: "California",
      tag: ["01 Tour", "02 Hotel", "04 Acitivity", "01 Restaurant"],
   },
   {
      id: 3,
      thumb: thumb_3,
      country: "Spain",
      tag: ["01 Tour", "02 Hotel", "04 Acitivity", "01 Restaurant"],
   },
   {
      id: 4,
      thumb: thumb_4,
      country: "Bail",
      tag: ["01 Tour", "02 Hotel", "04 Acitivity", "01 Restaurant"],
   },
]

const Destination = () => {
   return (
      <div className="tg-destination-area pt-135 pb-90">
         <div className="container">
            <div className="row">
               <div className="col-12">
                  <div className="tg-destination-section-title text-center mb-40">
                     <h5 className="tg-section-subtitle wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".6s">Most Popular Tour Packages </h5>
                     <h2 className="mb-15 wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".7s">Our Best Destination</h2>
                     <p className="text-none wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">Are you tired of the typical tourist destinations and looking<br />
                        to step out of your comfort zonetravel</p>
                  </div>
               </div>
               {destination_data.map((item) => (
                  <div key={item.id} className="col-lg-3 col-md-6 col-sm-6">
                     <div className="tg-destination-item mb-30 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".6s">
                        <div className="tg-destination-thumb fix p-relative">
                           <Image className="w-100" src={item.thumb} alt="des" />
                           <div className="tg-listing-2-mask">
                              <Image className="w-100" src={shape} alt="" />
                           </div>
                        </div>
                        <div className="tg-destination-content text-center">
                           <div className="tg-destination-meta">
                              <Link href="/map-listing">{item.country}</Link>
                           </div>
                           <div className="tg-destination-tag">
                              {item.tag.map((tag, i) => (
                                 <span key={i}>{tag}</span>
                              ))}
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

export default Destination
