export function CiteLogo() {
    return (
        <div className="flex items-center gap-3">
            <img
                src="/images/ucu-cite-logo.png"
                alt="UCU CITE seal"
                className="size-11 shrink-0 rounded-full object-cover"
            />
            <div className="leading-tight">
                <p className="text-sm font-semibold text-white">CITE Tracker</p>
                <p className="text-[11px] text-white/45">
                    Graduate Tracking System
                </p>
            </div>
        </div>
    );
}

export function CollegeCredit() {
    return (
        <p className="text-[10px] leading-relaxed tracking-wide text-white/55 uppercase">
            College of Information
            <br />
            and Technology Education
            <br />
            Urdaneta City University
        </p>
    );
}
