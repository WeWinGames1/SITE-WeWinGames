<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <WelcomeLayout>
        <Head title="About Us" />
        
        <div class="py-5" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%); min-height: 100vh;">
            <article class="container-fluid px-4 px-lg-5" style="max-width: 1200px;">
                <!-- Article Header -->
                <header class="mb-5">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        About Us
                    </h1>
                </header>

                <!-- Article Content -->
                <div class="article-content mb-5">
                    <h2>Our Story</h2>
                    <p>
                        Our European origins have given us a love of Sports betting as something to help us enjoy sport more. The author grew up in an Irish household where his father (a conservative teacher) spent every Saturday glued to the television. He was following the horse racing bets he had selected, live on TV. He spent a couple of evenings after school during the week assiduously putting his 'picks' together. On Saturday morning he would leave for the bookies immediately after breakfast to place his small bets. As soon as he arrived back, he would then command the TV for the rest of Saturday afternoon. It was a lifetime passion.
                    </p>
                    <p>
                        There is nothing more exciting than, not only seeing your team winning, but to also have a small reward as well when they do as you expect. Sports betting, like all things, should be done in moderation. You should never gamble more than you can afford. Having said that, many people like to take it seriously enough to ensure they can have the enjoyment of following their bets while ensuring they do not lose money. In addition, many hope to make a small return as well. In days of very low interest rates, the returns of 20%+ achievable in Sport-betting is not to be sniffed at.
                    </p>

                    <h2>Our Mission</h2>
                    <p>
                        Our mission with this website is to help you enjoy your Sports-betting more by giving you both simple and practical advice. Even better, we have exhaustively studied and analyzed all of the best international sources of betting tips, to provide you with what we believe are dependable picks, across all major US and international sports.
                    </p>
                    <p>
                        We want our customers to be able to access our picks no matter what their budget is, so we have created tiers from daily through monthly picks. We are confident that you will make money following our picks, the numbers shown for results on our site are based on standard $30 stakes. Our Betting Education section should also help you become a better bettor.
                    </p>
                    <p>
                        Good luck with your betting and please keep it responsible.
                    </p>

                    <h2>Our promise to you</h2>
                    <p>
                        At WeWinGames.com, we genuinely want you to become a better sports bettor. This is not a bland statement, and we back it up as follows-
                    </p>
                    <ol>
                        <li>
                            We provide you free and premium picks with only 1 objective in mind—to maximize returns. We are NOT tied into any particular tipsters, which means we are continually revising and exploring the various sources we use. These sources include a variety of American and International tipsters. We drop any who start to underperform from our betting picks and tips. We are continuously scouring the world for new quality tipping sources.
                        </li>
                        <li>
                            Unlike many US-based betting services, we do not try to FOOL you by displaying only fancy win ratios. This is for the simple reason that a win ratio on its own is almost meaningless. You must take the odds into account to know if the service is profitable, as well as the stake size. This is why we focus very much on ROI (Return on Investment) in our results.
                        </li>
                        <li>
                            We display our betting picks results completely transparently and summarized for you in easy-to-understand tables. Even more importantly, we show you all of the bets we have made ourselves and that make up our returns on our
                            <a href="https://docs.google.com/spreadsheets/d/1dNj41tUxP2sdnMLWJ_Oz_K9zrn8kT6Kd1AeuSraC7xw/edit#gid=2142298601" target="_blank">google sheet</a> linked into the website.
                        </li>
                        <li>
                            Line Shopping is a vital part to become a better sports bettor. Yet, most websites displaying a full odds comparison service leave out the Sportsbooks they are not affiliated with, as they do not earn money from those sites. At WeWinGames, we prefer to put the customer first, which is the reason that our
                            <a href="https://wewingames.com/odds-comparison/" target="_blank">Odds Comparison</a> shows all available books where we can access their feeds/odds.
                        </li>
                        <li>
                            Our betting education goes well beyond the bland articles and statements of many of our competitors, e.g. sportsbooks make their money off only margin which is simply not true—see our article on
                            <a href="https://wewingames.com/sportbooks-earn-profits-through-online-sports-betting/" target="_blank">How do Sportsbooks make their profit</a>.
                        </li>
                    </ol>
                </div>
            </article>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.article-content {
    color: #e5e7eb;
    font-size: 1.125rem;
    line-height: 1.75;
}

.article-content :deep(h1),
.article-content :deep(h2),
.article-content :deep(h3),
.article-content :deep(h4),
.article-content :deep(h5),
.article-content :deep(h6) {
    color: white;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-content :deep(h2) {
    font-size: 1.875rem;
}

.article-content :deep(h3) {
    font-size: 1.5rem;
}

.article-content :deep(p) {
    margin-bottom: 1.25rem;
}

.article-content :deep(a) {
    color: #6366F1;
    text-decoration: none;
}

.article-content :deep(a:hover) {
    color: #7C3AED;
    text-decoration: underline;
}

.article-content :deep(ul),
.article-content :deep(ol) {
    margin-bottom: 1.25rem;
    padding-left: 2rem;
}

.article-content :deep(li) {
    margin-bottom: 0.5rem;
}
</style>