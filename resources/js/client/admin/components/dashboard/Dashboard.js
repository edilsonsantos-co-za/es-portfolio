import React, { useEffect, useState } from 'react';
import moment from 'moment';
import PropTypes from 'prop-types';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import { Skeleton } from 'antd';
import { useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { GoKey } from 'react-icons/go';
import { BsBriefcase } from 'react-icons/bs';
import { GiSecretBook } from 'react-icons/gi';
import { BiArchive } from 'react-icons/bi';
import { AiOutlineTeam } from 'react-icons/ai';
import { RiServiceLine, RiMessage3Line } from 'react-icons/ri';

const formatNumber = (value) => new Intl.NumberFormat().format(value || 0);

/**
 * Smooth area sparkline drawn in the accent colour; scales to its container.
 */
const Sparkline = ({ values }) => {
    const width = 600;
    const height = 90;
    const max = Math.max(...values, 1);
    const step = values.length > 1 ? width / (values.length - 1) : width;
    const points = values.map((v, i) => [i * step, height - 4 - (v / max) * (height - 12)]);
    const line = points.map(([x, y], i) => {
        if (i === 0) {
            return `M${x},${y}`;
        }
        const [px, py] = points[i - 1];
        const cx = (px + x) / 2;
        return `C${cx},${py} ${cx},${y} ${x},${y}`;
    }).join(' ');

    return (
        <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" width="100%" height={height} aria-hidden="true">
            <defs>
                <linearGradient id="atlas-spark" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="var(--accent)" stopOpacity=".28"/>
                    <stop offset="100%" stopColor="var(--accent)" stopOpacity="0"/>
                </linearGradient>
            </defs>
            <path d={`${line} L${width},${height} L0,${height} Z`} fill="url(#atlas-spark)"/>
            <path d={line} fill="none" stroke="var(--accent)" strokeWidth="2.5" vectorEffect="non-scaling-stroke"/>
        </svg>
    );
};

Sparkline.propTypes = {
    values: PropTypes.arrayOf(PropTypes.number).isRequired,
};

/**
 * Atlas dashboard: content counts, visitor and message activity, and the site theme.
 */
const Dashboard = () => {
    let navigate = useNavigate();
    const { siteName } = useSelector(state => state.globalState);

    const todayStartDateUtc = moment.utc(moment().startOf('day')).format('YYYY-MM-DD HH:mm:ss');
    const todayEndDateUtc = moment.utc(moment().endOf('day')).format('YYYY-MM-DD HH:mm:ss');

    const thisWeekStartDateUtc = moment.utc(moment().startOf('week').startOf('day')).format('YYYY-MM-DD HH:mm:ss');
    const thisWeekEndDateUtc = moment.utc(moment().endOf('week').endOf('day')).format('YYYY-MM-DD HH:mm:ss');

    const thisMonthStartDateUtc = moment.utc(moment().startOf('month').startOf('day')).format('YYYY-MM-DD HH:mm:ss');
    const thisMonthEndDateUtc = moment.utc(moment().endOf('month').endOf('day')).format('YYYY-MM-DD HH:mm:ss');

    const [loading, setLoading] = useState(true);

    const [visitorData, setVisitorData] = useState({ total: 0, today: 0, thisWeek: 0, thisMonth: 0, trend: [] });
    const [messageData, setMessageData] = useState({ total: 0, today: 0, thisWeek: 0, thisMonth: 0 });
    const [skillData, setSkillData] = useState({ total: 0 });
    const [educationData, setEducationData] = useState({ total: 0 });
    const [experienceData, setExperienceData] = useState({ total: 0 });
    const [projectData, setProjectData] = useState({ total: 0 });
    const [servicesData, setServicesData] = useState({ total: 0 });

    useEffect(() => {
        loadData();
    }, []);

    const loadData = (_loading = true) => {
        setLoading(_loading);

        HTTP.get(Routes.api.admin.stats, {   
            params: {
                todayStartDate: todayStartDateUtc,
                todayEndDate: todayEndDateUtc,
                thisWeekStartDate: thisWeekStartDateUtc,
                thisWeekEndDate: thisWeekEndDateUtc,
                thisMonthStartDate: thisMonthStartDateUtc,
                thisMonthEndDate: thisMonthEndDateUtc,
            }
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                const result = response.data.payload;

                if (result) {
                    //visitors
                    let trendArray = [];

                    result.visitors.trend.forEach(element => {
                        trendArray.push(parseInt(element.count));
                    });

                    if (trendArray.length === 0) {
                        trendArray = [0, 0];
                    } else if (trendArray.length === 1) {
                        trendArray.unshift(0);
                    }

                    setVisitorData({
                        total: result.visitors.total,
                        today: result.visitors.totalToday,
                        thisWeek: result.visitors.totalThisWeek,
                        thisMonth: result.visitors.totalThisMonth,
                        trend: trendArray
                    });

                    //message
                    setMessageData({
                        total: result.message.total,
                        today: result.message.totalToday,
                        thisWeek: result.message.totalThisWeek,
                        thisMonth: result.message.totalThisMonth
                    });

                    //skills
                    setSkillData({
                        total: result.skills.total,
                    });

                    //education
                    setEducationData({
                        total: result.educations.total,
                    });

                    //experience
                    setExperienceData({
                        total: result.experiences.total,
                    });

                    //project
                    setProjectData({
                        total: result.projects.total,
                    });

                    //service
                    setServicesData({
                        total: result.services.total,
                    });

                }
            });
        })
        .catch(error => {
            Utils.handleException(error);
        }).finally(() => {
            setLoading(false);
        });
    }

    const contentStats = [
        { title: 'Projects', number: projectData.total, icon: <BiArchive/>, link: Routes.web.admin.portfolioProjects },
        { title: 'Experience', number: experienceData.total, icon: <BsBriefcase/>, link: Routes.web.admin.portfolioExperiences },
        { title: 'Skills', number: skillData.total, icon: <GoKey/>, link: Routes.web.admin.portfolioSkills },
        { title: 'Education', number: educationData.total, icon: <GiSecretBook/>, link: Routes.web.admin.portfolioEducation },
        { title: 'Services', number: servicesData.total, icon: <RiServiceLine/>, link: Routes.web.admin.portfolioServices },
    ];

    const activity = (data) => [
        { label: 'This month', value: data.thisMonth },
        { label: 'This week', value: data.thisWeek },
        { label: 'Today', value: data.today },
    ];

    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : (hour < 18 ? 'Good afternoon' : 'Good evening');
    const [leadStat, ...otherStats] = contentStats;

    const statTile = (stat, className = '') => (
        <button key={stat.title} type="button" className={`atlas-tile atlas-stat ${className}`} onClick={() => navigate(stat.link)}>
            <span className="atlas-stat-icon" aria-hidden="true">{stat.icon}</span>
            <span className="atlas-kicker">{stat.title}</span>
            {loading ? <Skeleton.Button active size="small"/> : <span className="atlas-stat-number">{formatNumber(stat.number)}</span>}
        </button>
    );

    // One bento, the admin's variant of the Atlas layout: a welcome tile leads, activity sits beside it,
    // and the content counts close the grid.
    return (
        <div className="atlas-dash">
            <header className="atlas-page-head">
                <div>
                    <p className="atlas-kicker">Admin overview</p>
                    <h1>Dashboard</h1>
                </div>
            </header>

            <section className="atlas-bento" aria-label="Overview">
                <article className="atlas-tile atlas-welcome">
                    <p className="atlas-kicker">{siteName || 'ES Portfolio'}</p>
                    <h2>{greeting}.</h2>
                    <p className="atlas-muted">Your portfolio runs on Atlas, the design shared by the portfolio, the blog and their admins. Install other looks from Themes.</p>
                    <div className="atlas-actions">
                        <a className="atlas-btn" href={Routes.web.frontend.home} target="_blank" rel="noreferrer">View {siteName || 'site'} ↗</a>
                        <button type="button" className="atlas-btn ghost" onClick={() => navigate(Routes.web.admin.portfolioThemes)}>Themes</button>
                        <button type="button" className="atlas-btn ghost" onClick={() => navigate(Routes.web.admin.portfolioConfig)}>Site settings</button>
                        {Utils.journalUrl && <a className="atlas-btn ghost" href={Utils.journalUrl} target="_blank" rel="noreferrer">ES Journal ↗</a>}
                    </div>
                </article>

                <article className="atlas-tile atlas-activity atlas-span-2">
                    <div className="atlas-tile-head">
                        <span className="atlas-stat-icon" aria-hidden="true"><AiOutlineTeam/></span>
                        <div>
                            <p className="atlas-kicker">Visitors</p>
                            <p className="atlas-big">{loading ? '–' : formatNumber(visitorData.total)}</p>
                        </div>
                        <button type="button" className="atlas-link" onClick={() => navigate(Routes.web.admin.visitors)}>Open →</button>
                    </div>
                    <div className="atlas-trend" aria-hidden="true">
                        {!loading && <Sparkline values={visitorData.trend}/>}
                    </div>
                    <dl className="atlas-split">
                        {activity(visitorData).map(item => (
                            <div key={item.label}><dt className="atlas-kicker">{item.label}</dt><dd>{loading ? '–' : formatNumber(item.value)}</dd></div>
                        ))}
                    </dl>
                </article>

                <article className="atlas-tile atlas-activity">
                    <div className="atlas-tile-head">
                        <span className="atlas-stat-icon" aria-hidden="true"><RiMessage3Line/></span>
                        <div>
                            <p className="atlas-kicker">Messages</p>
                            <p className="atlas-big">{loading ? '–' : formatNumber(messageData.total)}</p>
                        </div>
                        <button type="button" className="atlas-link" onClick={() => navigate(Routes.web.admin.messages)}>Open →</button>
                    </div>
                    <dl className="atlas-split">
                        {activity(messageData).map(item => (
                            <div key={item.label}><dt className="atlas-kicker">{item.label}</dt><dd>{loading ? '–' : formatNumber(item.value)}</dd></div>
                        ))}
                    </dl>
                </article>

                {statTile(leadStat, 'is-accent')}
                {otherStats.map(stat => statTile(stat))}
            </section>
        </div>
    )
}

export default React.memo(Dashboard);
