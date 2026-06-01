import Image from "next/image"
import comment from "@/assets/img/blog/details/avatar.png"

const Comment = () => {
   return (
      <div className="tg-tour-about-cus-review-wrap tg-blog-details-review mb-25">
         <ul>
            <li className="mb-40">
               <div className="tg-tour-about-cus-review d-flex">
                  <div className="tg-tour-about-cus-review-thumb">
                     <Image src={comment} alt="avatar" />
                  </div>
                  <div>
                     <div className="tg-tour-about-cus-name">
                        <span>Author</span>
                        <h6>Parker Willy</h6>
                     </div>
                     <p className="text-none lh-28 mb-10">Finanappreciate your trust greatly Our clients choose dentace ducts because know we are the best area Awaitingare really.Seorem.</p>
                  </div>
               </div>
            </li>
         </ul>
      </div>
   )
}

export default Comment
