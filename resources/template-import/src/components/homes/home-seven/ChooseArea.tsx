import Image from "next/image"
import { JSX } from "react"
import Link from "next/link"

import shape from "@/assets/img/chose/chose-5/map.png"
import choose_thumb1 from "@/assets/img/chose/chose-5/chose-2.jpg"
import choose_thumb2 from "@/assets/img/chose/chose-5/chose-3.jpg"
import choose_thumb3 from "@/assets/img/chose/chose-5/chose-1.jpg"
import circle from "@/assets/img/chose/chose-5/text.png"
import circle2 from "@/assets/img/chose/chose-5/icon.png"
import Button from "@/components/common/Button"
import Choose3 from "@/svg/home-one/Choose3"
import Choose1 from "@/svg/home-one/Choose1"
import Choose2 from "@/svg/home-one/Choose2"

interface DataType {
   id: number;
   icon: JSX.Element;
   title: string;
   desc: string;
}

const choose_data: DataType[] = [
   {
      id: 1,
      icon: (<><Choose1 /></>),
      title: "Best Price Guarantee",
      desc: "Are you tired of the typical tourist destinatio and looking  step out of your comfort.",
   },
   {
      id: 2,
      icon: (<><Choose2 /></>),
      title: "Easy & Quick Booking",
      desc: "Are you tired of the typical tourist destinatio and looking  step out of your comfort.",
   },
   {
      id: 3,
      icon: (<><Choose3 /></>),
      title: "Secure Journey With Us",
      desc: "Are you tired of the typical tourist destinatio and looking  step out of your comfort.",
   },
]

const ChooseArea = () => {
   return (
      <div className="tg-chose-area pt-140 pb-130 p-relative z-index-1">
         <Image className="tg-chose-5-map-shape d-none d-lg-block" src={shape} alt="" />
         <div className="container">
            <div className="row align-items-center">
               <div className="col-lg-7">
                  <div className="tg-chose-section-title mb-30">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".1s">Dream Your Next Trip</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">discover when even<br /> you want to go</h2>
                  </div>
               </div>
               <div className="col-lg-5">
                  <div className="tg-chose-5-para">
                     <p className="text-none wow fadeInUp mb-0" data-wow-delay=".5s" data-wow-duration=".9s">Are you tired of the typical tourist destinations and looking
                        r comfort zon. Adventure.Are you tirehe typical touriststin
                        ations and looking Are you tired of the typical tourist de
                        stinations and looking re you tired of the.</p>
                  </div>
               </div>
            </div>

            <div className="row">
               <div className="col-lg-7 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration="1s">
                  <div className="tg-chose-5-left mr-40">
                     <div className="row">
                        <div className="col-lg-5 col-md-5 col-sm-5">
                           <div className="tg-chose-5-thumb">
                              <Image className="mb-20" src={choose_thumb1} alt="" />
                              <Image src={choose_thumb2} alt="" />
                           </div>
                        </div>
                        <div className="col-lg-7 col-md-7 col-sm-7">
                           <div className="tg-chose-5-thumb-2 p-relative">
                              <div className="tg-chose-5-text-round d-none d-sm-block">
                                 <div className="tg-chose-3-rounded p-relative mb-20">
                                    <Image className="rotate-infinite-2" src={circle} alt="" />
                                    <Image className="tg-chose-3-star rounded-circale" src={circle2} alt="" />
                                 </div>
                              </div>
                              <Image src={choose_thumb3} alt="" />
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

               <div className="col-lg-5">
                  <div className="tg-chose-list-wrap tg-chose-5-list-wrap pt-40">
                     {choose_data.map((item) => (
                        <div key={item.id} className="tg-chose-list d-flex mb-20 wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".9s">
                           <span className="tg-chose-list-icon mr-20">
                              {item.icon}
                           </span>
                           <div className="tg-chose-list-content">
                              <h4 className="tg-chose-list-title mb-5">{item.title}</h4>
                              <p>{item.desc}</p>
                           </div>
                        </div>
                     ))}
                     <div className="tg-chose-btn wow fadeInUp" data-wow-delay=".8s" data-wow-duration=".9s">
                        <Link href="/tour-details-2" className="tg-btn tg-btn-switch-animation">
                           <Button text="Book Your Trip" />
                        </Link>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default ChooseArea
