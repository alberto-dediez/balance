Feature:
    I want to make sure that the owner of an account manages the entries of its contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner lists the entries of a contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        Given contest "C1" has 2 confirmed entries
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then the response status should be 200
        Then I see a text "Ana García"
        Then I see a text "ana@example.com"
        Then I see a text "Congreso anual"
        Then I see a table with 4 rows
        Then I see a link to "/es/contest_entries/export?contestId=C1"

    Scenario: The owner draws the winners of a closed contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" has 2 winners
        Given contest "C1" is drawn by the platform
        Given contest "C1" is published
        Given contest "C1" has 3 confirmed entries
        Given contest "C1" is closed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Realizar sorteo"
        When I go to "/es/carbon-footprint/contests/C1/draw" with csrf token "draw"
        Then I am redirected to route "contests_entries"
        Then I see a text "Sorteo realizado con éxito"
        Then I see a text "Candidatos del sorteo"
        Then I do not see a text "Realizar sorteo"
        When I filter by "winner" with "1"
        Then I see a table with 3 rows

    Scenario: The owner removes an entry and its prize goes to the next one
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" with participation "calculation" and award "first_entrants" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        Given contest "C1" has a confirmed entry with id "N2" named "Bea López" and email "bea@example.com"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries/N1/remove" with csrf token "remove"
        Then I am redirected to route "contests_entries"
        Then I see a text "Inscripción eliminada con éxito"
        Then I do not see a text "Ana García"
        When I filter by "winner" with "1"
        Then I see a text "Bea López"

    Scenario: The owner disqualifies an entry with a reason
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" with participation "calculation" and award "first_entrants" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        Given contest "C1" has a confirmed entry with id "N2" named "Bea López" and email "bea@example.com"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries/N1/disqualify"
        Then the response status should be 200
        Then I see a text "La descalificación no se puede deshacer."
        Then I see a text "Su premio pasará a otra persona apuntada."
        When I submit a form with "reason:Datos falsos" and button "Descalificar"
        Then I am redirected to route "contests_entries"
        Then I see a text "Inscripción descalificada con éxito"
        Then I see a text "Ana García"
        Then I see a text "Descalificado"
        Then I see a text "Datos falsos"
        When I filter by "winner" with "1"
        Then I see a text "Bea López"
        Then I do not see a text "Ana García"

    Scenario: The disqualification requires a reason
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries/N1/disqualify"
        When I submit a form with "reason:" and button "Descalificar"
        Then the response status should be 200
        Then I do not see a text "Inscripción descalificada con éxito"

    Scenario: The owner purges the personal data of a closed contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        Given contest "C1" has a confirmed entry with id "N1" named "Ana García" and email "ana@example.com"
        Given contest "C1" is closed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then I see a text "Purgar datos personales"
        When I go to "/es/carbon-footprint/contests/C1/purge" with csrf token "purge"
        Then I am redirected to route "contests_entries"
        Then I see a text "Datos personales purgados con éxito"
        Then I do not see a text "Ana García"
        Then I do not see a text "ana@example.com"
        Then I see a table with 2 rows

    Scenario: An admin that is not impersonating cannot see the entries
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then the response status should be 403

    Scenario: A distributor cannot see the entries of a contest of a distributed account
        Given account "Distribuidor" with id "D1" exists
        Given account "D1" is a distributor
        Given account "D1" has the contests feature enabled
        Given distributor "D1" is assigned to account "A1"
        Given user "distributor@dediez.software" with role "ROLE_OWNER" of account "D1" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "distributor@dediez.software"
        When I go to "/es/carbon-footprint/contests/C1/entries"
        Then the response status should be 403

    Scenario: The owner exports the entries of a contest
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        Given contest "C1" is published
        Given contest "C1" has 2 confirmed entries
        When I am logged as "owner@dediez.software"
        When I go to "/es/contest_entries/export?contestId=C1"
        Then the response status should be 200
        Then I see a form field "[filter][contestId]" with value "C1"
        When I submit a form with "[filter][contestId]:C1" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: The owner of another account is not offered the contest to export
        Given account "Otra" with id "A2" exists
        Given account "A2" has the contests feature enabled
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "A2" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/contest_entries/export?contestId=C1"
        Then the response status should be 200
        Then I do not see an option "C1" in form field "[filter][contestId]"

    Scenario: A user of the account cannot export the entries
        Given user "user@dediez.software" with role "ROLE_USER" of account "A1" exists
        Given contest "Concurso de verano" with id "C1" of account "A1" for "event" "E1" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/contest_entries/export?contestId=C1"
        Then the response status should be 403
