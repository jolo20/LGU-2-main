document.getElementById('btnLogin').addEventListener('click', function(){
      const e = document.getElementById('email').value.trim();
      const p = document.getElementById('password').value.trim();
      if(!e || !p){
        alert('Please enter email and password');
        return;
      }
      // fake login
      this.disabled = true;
      this.textContent = 'Signing in...';
      setTimeout(()=>{
        alert('Welcome — login simulated');
        this.disabled = false;
        this.textContent = 'LOGIN';
        document.getElementById('email').value='';
        document.getElementById('password').value='';
      }, 900);
    });