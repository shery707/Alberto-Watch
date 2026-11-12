$(function () {
  const api = "api/store.php";
  const cartKey = "albertoCart";
  let currentUser = null;
  const money = value => "$" + Number(value || 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
  async function request(action, data={}) {
    const response = await fetch(api,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json","Accept":"application/json"},body:JSON.stringify({action,...data})});
    let result; try { result=await response.json(); } catch(e) { throw new Error("Invalid server response. Check XAMPP and PHP."); }
    if(!response.ok || !result.success) throw new Error(result.message || "Request failed.");
    return result;
  }
  function getCart(){ try { const c=JSON.parse(localStorage.getItem(cartKey)||"[]"); return Array.isArray(c)?c:[]; } catch(e){return [];} }
  function saveCart(cart){ localStorage.setItem(cartKey,JSON.stringify(cart)); renderCart(); }
  function findProduct(id){ return watchProducts.find(p=>Number(p.id)===Number(id)); }
  function cartTotal(){ return getCart().reduce((sum,item)=>{const p=findProduct(item.id);return sum+(p?Number(p.price)*Number(item.qty):0);},0); }
  function renderCart(){
    const cart=getCart(); const count=cart.reduce((s,i)=>s+Number(i.qty),0);
    $("#cartCount").text(count); $("#cartItems").empty();
    if(!cart.length){$("#cartItems").html('<div class="cart-empty"><i class="bi bi-bag"></i><h3>Your bag is empty</h3><p>Browse the collection to add a watch or clock</p></div>');}
    cart.forEach(item=>{
      const p=findProduct(item.id); if(!p) return;
      $("#cartItems").append(`<div class="cart-row" data-id="${p.id}"><img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}"><div class="cart-row-info"><b>${escapeHtml(p.name)}</b><small>${escapeHtml(p.ref||"")}</small><strong>${money(p.price)}</strong></div><div class="cart-qty"><button type="button" class="qty-minus" aria-label="Decrease">−</button><span>${item.qty}</span><button type="button" class="qty-plus" aria-label="Increase">+</button><button type="button" class="cart-remove" aria-label="Remove item"><i class="bi bi-trash"></i></button></div></div>`);
    });
    $("#cartTotal").text(money(cartTotal()));
  }
  function addToCart(id){
    const p=findProduct(id); if(!p) return;
    const cart=getCart(); const item=cart.find(i=>Number(i.id)===Number(id));
    if(item) item.qty=Math.min(20,Number(item.qty)+1); else cart.push({id:Number(id),qty:1});
    saveCart(cart);
    const btn=$(".cart-nav-btn"); btn.addClass("cart-bump"); setTimeout(()=>btn.removeClass("cart-bump"),350);
    const addBtn=$(`.add-cart-btn[data-product-id="${Number(id)}"]`);
    const oldHtml=addBtn.html(); addBtn.addClass("is-added").html(`<i class="bi bi-check-lg"></i> Added`);
    clearTimeout(addBtn.data("resetTimer"));
    addBtn.data("resetTimer", setTimeout(()=>addBtn.removeClass("is-added").html(oldHtml),1600));
  }
  // Inject a dedicated add-to-cart control without changing the product detail click target.
  function addProductButtons(){
    $("#productGrid .product-card").each(function(){
      const card=$(this); if(card.find(".add-cart-btn").length) return;
      const id=Number(card.attr("data-id"));
      card.find(".product-info").append(`<button type="button" class="add-cart-btn" data-product-id="${id}"><i class="bi bi-bag-plus"></i> Add to cart</button>`);
    });
  }
  const grid=document.getElementById("productGrid");
  if(grid) new MutationObserver(addProductButtons).observe(grid,{childList:true,subtree:true});
  addProductButtons();
  $("#productGrid").on("click",".add-cart-btn",function(e){e.preventDefault();e.stopPropagation();addToCart($(this).data("product-id"));});
  $("#cartItems").on("click",".qty-minus",function(){const id=Number($(this).closest(".cart-row").data("id"));const c=getCart();const i=c.find(x=>Number(x.id)===id);if(i){i.qty--;if(i.qty<=0)c.splice(c.indexOf(i),1);}saveCart(c);});
  $("#cartItems").on("click",".qty-plus",function(){const id=Number($(this).closest(".cart-row").data("id"));const c=getCart();const i=c.find(x=>Number(x.id)===id);if(i)i.qty=Math.min(20,i.qty+1);saveCart(c);});
  $("#cartItems").on("click",".cart-remove",function(){const id=Number($(this).closest(".cart-row").data("id"));saveCart(getCart().filter(x=>Number(x.id)!==id));});
  $("#cartModal").on("show.bs.modal",renderCart);

  async function refreshUser(){
    try{
      const res=await fetch("api/auth.php",{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json"},body:JSON.stringify({action:"status"})});
      const data=await res.json(); currentUser=data.user||null;
      if(currentUser && currentUser.role==="customer") $("#checkoutName").val(currentUser.name);
    }catch(e){currentUser=null;}
  }
  $("#checkoutForm").on("submit",async function(e){
    e.preventDefault();
    if(!getCart().length){$("#checkoutMessage").text("Your cart is empty.");return;}
    await refreshUser();
    if(!currentUser || currentUser.role!=="customer"){
      $("#checkoutMessage").text("Please login with your customer account before checkout.");
      bootstrap.Modal.getOrCreateInstance(document.getElementById("cartModal")).hide();
      bootstrap.Modal.getOrCreateInstance(document.getElementById("authModal")).show();
      return;
    }
    const button=$("#checkoutButton").prop("disabled",true).text("Placing order...");
    try{
      const result=await request("checkout",{items:getCart(),customer_name:$("#checkoutName").val().trim(),phone:$("#checkoutPhone").val().trim(),address:$("#checkoutAddress").val().trim()});
      saveCart([]); $("#checkoutForm")[0].reset(); $("#checkoutName").val(currentUser.name);
      $("#checkoutMessage").text(result.message+" Save your order ID: "+result.data.id);
      $("#myOrders").html(`<p class="order-success">Order ID: <b>${escapeHtml(result.data.id)}</b></p>`);
    }catch(error){$("#checkoutMessage").text(error.message);}
    finally{button.prop("disabled",false).html('Place order');}
  });
  $("#loadMyOrders").on("click",async function(){
    try{
      await refreshUser(); if(!currentUser || currentUser.role!=="customer") throw new Error("Please login with your customer account first.");
      const result=await request("my_orders");
      if(!result.data.length){$("#myOrders").html('<p class="text-secondary small mt-2">You have not placed any orders yet.</p>');return;}
      $("#myOrders").html(result.data.map(o=>`<div class="my-order"><b>${escapeHtml(o.id)}</b><span>${escapeHtml(o.status)}</span><small>${new Date(o.created_at).toLocaleDateString()} · ${money(o.total)}</small></div>`).join(""));
    }catch(error){$("#myOrders").html(`<p class="auth-message">${escapeHtml(error.message)}</p>`);}
  });

  // Footer contact form sends the enquiry to messages.json for the admin inbox.
  $("#contactForm").on("submit",async function(e){
    e.preventDefault(); const button=$(this).find('button[type="submit"]').prop("disabled",true);
    $("#formMessage").text("Sending your message...");
    try{
      const result=await request("message_create",{name:$("#name").val(),email:$("#email").val(),message:$("#message").val()});
      $("#formMessage").text(result.message); this.reset();
    }catch(error){$("#formMessage").text(error.message);}
    finally{button.prop("disabled",false);}
  });

  // Load editable product catalogue from JSON so admin changes appear on the shop.
  request("products").then(result=>{
    if(Array.isArray(result.data) && result.data.length){
      watchProducts=result.data;
      window.dispatchEvent(new Event("productsUpdated"));
      addProductButtons();
    }
  }).catch(error=>console.warn("Could not load server product catalogue:",error.message));
  renderCart();
});
