Feature:
    I want two accounts with different themes to render differently in the same process

    Scenario: An account on the Aurora theme gets the Aurora palette, stylesheet and templates
        Given account "AuroraCorp" with id "aurora-account" exists
        Given account "aurora-account" has branding with theme "aurora"
        Given event "Evento Aurora" with id "ev-aurora" of account "aurora-account" exists
        Given attendee survey with id "AS-AURORA" and token "token-aurora" for event "ev-aurora" of account "aurora-account" exists
        Given attendee survey "AS-AURORA" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-aurora/intercity"
        Then the response status should be 200
        Then I see a text "--brand-primary:#5B2A86"
        Then I see a text "family=Manrope"
        Then I see a text "styles/themes/aurora"

    Scenario: An account on the default theme is unaffected in the same process
        Given account "PlainCorp" with id "plain-account" exists
        Given event "Evento Plano" with id "ev-plain" of account "plain-account" exists
        Given attendee survey with id "AS-PLAIN" and token "token-plain" for event "ev-plain" of account "plain-account" exists
        Given attendee survey "AS-PLAIN" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-plain/intercity"
        Then the response status should be 200
        Then I see a text "--brand-primary:#3E7490"
        Then I do not see a text "styles/themes/aurora"

    Scenario: The Aurora theme overrides the status page template
        Given account "AuroraCorp" with id "aurora-account" exists
        Given account "aurora-account" has branding with theme "aurora"
        Given event "Evento Aurora" with id "ev-aurora" of account "aurora-account" exists
        Given attendee survey with id "AS-CLOSED" and token "token-closed" for event "ev-aurora" of account "aurora-account" exists
        Given attendee survey "AS-CLOSED" is closed with 100 real attendees and confidence level "95"
        When I go to "/es/carbon-footprint/attendee-survey/token-closed"
        Then I see a text "Esta encuesta ya se ha cerrado"
        Then I see a text "card-body text-center py-6 px-4"
        Then I do not see a text "col-xl-5"
