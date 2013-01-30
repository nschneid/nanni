nanni
=====

Nice Annotation Interface for text

Nathan Schneider (nschneid@cs.cmu.edu)

Status: IN DEVELOPMENT


# Goals

 * modular specification of multiple forms of annotation
 * multiple users (annotators)
 * short items (e.g. sentences)
 * everything stored in text files
 * written in Javascript and PHP
 * works in Firefox

# Preliminary

 * annotation persistence (for an item and user); not yet supported for 
   (preposition) tags. overridden with &new.
 * version history: viewing previous annotations of an item, including multiple 
   users' annotations side-by-side
 * ability to log in as a compound user: e.g. annotations for a user 'alice+bob' 
   can be edited by either 'alice' or 'bob' if they specify &u=alice+bob
 * previous/next buttons that do not save anything
     - TODO: the button should be red on hover if there are unsaved changes, 
       defined as: (a) the annotation in the text box differs from the user's 
       latest annotation, and (b) if &new, the user has modified the text box 
       since page load
     - turned off with &nonav. TODO: if &nonav is specified, submit button should 
       save but not continue to the next item.

# Upcoming features

 * item index for more flexible navigation/status summary

# Possible features

 * searching of items/annotations
 * user groups
 * multiple items per page
 * annotation queues (data assigned to multiple users with minimum/maximum 
   number of annotations per item)


# Some working documentation

## Querystring options

  * `split=DATASET` **(required)**
  * `from=STARTOFFSET` **(required)**: indexes the first item to show on this page
  * `to=STOPOFFSET` (default: `-1`): victory will be declared once the user has 
    reached this item offset, even if more items are available in the source data
  * `u=USERID` (default: authenticated user): unless the authenticated user is an admin, 
    this must contain the authenticated user's alias, separated by `+` signs. E.g. 
    authenticated users `alice` and `bob` can both edit annotations under the 
    `alice+bob` user.
  * `new`: default to the raw sentence, even if previously annotated
  * `readonly`: prevent the annotations from being edited
      - TODO: prep tags?
      - TODO: prevent submit?
  
### Navigation and links

  * `nosubmit`: hides the save button
  * `nonav`: turns off the navigation to other sentences
  * `inst`: controls which version of the instructions will be linked to
  * `noinstr`: hides the instructions link at the bottom of the page

### Kinds of annotation

  * `prep`: enable preposition tag annotations
  * `initialStage=VAL` (default: `0`): which annotation step the user will start with; 
    submit will progress to the next step, if there is one. currently, `0`=MWEs only, 
    `1`=MWEs+preposition tags if these are enabled.

### Annotation version history

  * `v`: show an embedded menu and read-only previewer of previous annotations of the sentence
      - `v`: the version browser is collapsed by default
      - `v=+`: the version browser is expanded by default
      - `v=USERID`: the version browser is restricted to versions from a particular user
      - `v=+USERID`: user-restricted, expanded by default
      - `v[]=USERID1&v[]=+USERID2`: multiple version browsers for multiple users
      - `nosubmit&noinstr&readonly&embedded` is specified on the embedded page
  * `versions`: show menu of previous annotations of the sentence that will replace the 
    one currently being edited
  * `nooutdated`: in the versions menu, only the most recent annotation from a given 
    user will be displayed
  * `embedded`: indicates the page is loaded within an iframe

## File layout

  * `data/DATASET`: file containing input items, one per line
  * `users/USERID/DATASET.nanni`: output file with the user's most recent annotation of each item
  * `users/USERID/DATASET.nanni.all`: output file with all the user's annotations, in 
    chronological order
