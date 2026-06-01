import blog_data from "@/data/BlogData"
import Image from "next/image"
import Link from "next/link"

interface PropTypes {
   style: boolean;
}

const Blog = ({ style }: PropTypes) => {
   return (
      <div className={`tg-blog-area pt-130 p-relative z-index-1 ${style ? "tg-blog-su-2-wrapper pb-100" : "tg-blog-space-2 tg-blog-su-wrapper"}`}>
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-6 col-md-8">
                  <div className="tg-location-section-title text-center mb-30">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-20 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Tips and Inspiration</h5>
                     <h2 className="tg-section-su-title text-none wow fadeInUp mb-15" data-wow-delay=".5s" data-wow-duration=".9s">Latest News & Articles</h2>
                     <p className="tg-section-su-para tg-section-su-para-2 mb-10">Are you tired of the typical tourist destinations and looking <br />
                        to step out of your comfort zonetravel</p>
                  </div>
               </div>
            </div>
            <div className="row">
               {blog_data.filter((items) => items.page === "home_1").map((item) => (
                  <div key={item.id} className="col-xl-4 col-lg-6 col-md-6 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration=".9s">
                     <div className="tg-blog-item tg-blog-2-item mb-25">
                        <div className="tg-blog-thumb p-relative fix mb-25">
                           <Link href="blog-details"><Image className="w-100" src={item.thumb} alt="blog" /></Link>
                           <span className="tg-blog-tag p-absolute">{item.tag}</span>
                        </div>
                        <div className="tg-blog-content  p-relative">
                           <h3 className="tg-blog-title mb-15"><Link href="/blog-details">{item.title}</Link></h3>
                           <div className="tg-blog-date">
                              <span className="mr-20"><i className="fa-light fa-calendar"></i> {item.date}</span>
                              <span><i className="fa-regular fa-clock"></i> {item.time}</span>
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

export default Blog
