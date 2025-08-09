
        * { font-family: 'Kanit', sans-serif; font-style: normal; font-weight: 400; }
        body { background: linear-gradient(135deg, #f0f4f8 0%, #e8edf3 100%); color: #2c3e50; overflow-x: hidden; }
        .navbar { background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
        .btn-primary { background: linear-gradient(45deg, #0a5f97 0%, #0d96d2 100%); color: white; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(13, 150, 210, 0.3); }
        .btn-primary:hover { background: linear-gradient(45deg, #0d96d2 0%, #0a5f97 100%); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13, 150, 210, 0.5); }
        .btn-danger { background-color: #ef4444; color: white; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background-color: #dc2626; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4); }
        .btn-secondary { background-color: #6c757d; color: white; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2); }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(108, 117, 125, 0.4); }
        .text-gradient { background: linear-gradient(45deg, #0a5f97, #0d96d2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .pixellink-logo { font-weight: 700; font-size: 2.25rem; background: linear-gradient(45deg, #0a5f97, #0d96d2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .pixellink-logo b { color: #0d96d2; }
        .card-item { background: white; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s ease; flex-shrink: 0; }
        .card-item:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }
        .card-image { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-top-left-radius: 1rem; border-top-right-radius: 1rem; }
        .feature-icon { color: #0d96d2; transition: transform 0.3s ease; }
        .fade-in-slide-up { opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease-out, transform 0.8s ease-out; }
        .fade-in-slide-up.is-visible { opacity: 1; transform: translateY(0); }

    @media (max-width: 768px) {
        .hero-section { padding: 6rem 0; }
        .hero-section h1 { font-size: 2.8rem; }
        .hero-section p { font-size: 1rem; }
        .hero-section .space-x-0 { flex-direction: column; gap: 1rem; }
        .hero-section .btn-primary, .hero-section .btn-secondary { width: 90%; max-width: none; font-size: 0.9rem; padding: 0.75rem 1.25rem; }
        .pixellink-logo { font-size: 1.6rem; }
        .navbar .px-5 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .navbar .py-2 { padding-top: 0.3rem; padding-bottom: 0.3rem; }
        h2 { font-size: 1.8rem; }
        .card-item { border-radius: 0.75rem; padding: 1rem; }
        .card-image { height: 160px; }
        .sm\:grid-cols-2 { grid-template-columns: 1fr; }
        .flex-col.sm\:flex-row>*:not(:last-child) { margin-bottom: 1rem; }
        .md\:mb-0 { margin-bottom: 1rem; }
        .footer-links { flex-direction: column; gap: 0.5rem; }
    }
        
    @media (max-width: 480px) {
        .hero-section h1 { font-size: 2.2rem; }
        .hero-section p { font-size: 0.875rem; }
        .pixellink-logo { font-size: 1.4rem; }
        h2 { font-size: 1.5rem; }
        .container { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1rem; padding-right: 1rem; }
        .p-10 { padding: 1.5rem; }
        .card-item { padding: 0.75rem; }
        .card-image { height: 120px; }
    }
