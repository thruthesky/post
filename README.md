# Drupal 버젼 8 게시판 모듈
# 안내


드루팔 버젼 8 의 기본 Forum 모듈은 한국형 게시판이 아니며 한국형 게시판에 맞도록 개조(또는 기능추가)를 하는 것 또한 코드가 매우 복잡해져 쉽지 않습니다.

그래서 한국형에 맞는 새로운 게시판 모듈을 만들고자 "post" 모듈을 개발하게 되었습니다.

# 저작권

작성자 : 송재호

연락처 : thruthesky@gmail.com 070-7529-1749

저작권 : 먹고 살기 힘들어서 이용 권한을 아래와 같이 제한합니다.

* 본 모듈을 사용하기 위해서는 www.witheng.com 홈페이지에서 한달 영어 공부를 해야 합니다.


## 작업 로그
* 2015년 6월 30일 1차 초안 완료.
아래의 남은 기능은 최단 시간내에 해결해야 하는 기능이며 조만간에 해결 될 듯. 

## 남은 기능


* report. 나쁜 글을 리포팅하는 카운트 수. 찬/반 투표와 마찬가지로 로그를 가리고 두번 리포팅 못하도록 할 것.
* 비밀게시판 ( secret ) : 두가지 옵션. 1. 사용자 선택. 2. 관리자가 무조건 해당 게시판을 비밀게시판을 지정.
* 글 이동
* 파일업로드/삭제
* id of first images
* PostReminder entity 를 만들고, 공지사항 번호를 기록해서 각 게시판 상단에 표시 할 것. 즉, post_data 테이블에 공지사항 표시를 따로 하지 않는다. 또는 공지사항 글은 post_data 에 저장하지 않고, post_reminder 에 따로 저장을 한다.
* 게시판 전체 관리자/부 관리자, 게시판 별 관리자/부관리자
* domain, user_agent, referer
* private title, content, content_stript is for secret post, blinded post, blocked post, deleted post( undelete may be )
* RSS
* Web API For IFrame
* 블라인드/차단 with reason.
* shortcut 을 활용한 /reminder/short-cut-name 그리고 "/notice/숏컷이름" 과 같이 할 것.
* 카테고리 기능. 카테고리 하나당 게시판 하나. 게시판 설정에 카테고리를 두는 것이 아니라, 여러 게시판을 library 의 category 기능으로 그룹으로 묶는 기능을 통해서 카테고리 관리를 한다.
이 것은 카테고리 캐시가 필요하다.
* browser_id 기록. 이것은 library 의 member entity 에서 browser_id 를 먼저 기록 해 주어야 한다. 
* country, province, city 의 활용. 필리핀 데이터를 기본 저장.
* browser_id 를 바탕으로 이중 아이디와 모든 글을 찾아 낼 것.
* 쪽지기능. library 모듈에서 쪽지 기능이 되어야 함. 쪽지 기능은 핸드폰 문자로 연동을 할 것.

## 장기적으로 해결 해야 할 점.
* 글 리스트 및 검색 속도 ( 인덱싱이 어떻게 타는지. )



# 사용자 도움말

## 글 삭제

1. 글을 삭제 하는 경우, 코멘트가 적혀 있지 않은 경우 글이 바로 삭제됩니다.
2. 코멘트가 있는 경우, "삭제되었습니다." 로 표시가 됩니다.
	1. 이 때에는 모든 코멘트가 다 삭제되고 마지막 코멘트 또는 글이 삭제 될 때 해당 전체 쓰레드가 삭제됩니다. 
3. 강제 삭제를 하면, 코멘트가 삭제되지 않아도 해당 쓰레드의 모든 코멘트와 글을 삭제해 버립니다. 강제 삭제는 관리자만 할 수 있습니다.


## 검색
* 특정 게시판에서 검색을 하면 해당 게시판만 검색. 이 때, "?post_config_name=게시판이름" 과 같이 지정.
* 전체 검색을 하면, 전체 게시판에서 검색 됨. 이 때, "?post_config_name=" 에는 값이 들어가지 않음.

 


# 개발자 도움말



## 의존성
본 모듈은 Withcenter Library 모듈이 필요합니다.
https://github.com/thruthesky/library 를 참고하십시오.

## Entity

기본 Entity 인 Node 의 활용 방안을 검토해 보았지만 게시판 별 그룹이나 검색 등에 있어서 기본 Node Entity 를 활용하는 것은 코드가 복잡해져 개발 작업 및 유지 보수가 힘들어 새로운 Entity 를 만들어 사용합니다. 

### PostConfig Entity, PostData Entity
위와 같이 기본적으로 두개의 entity 를 사용하며 파일 업로드는 기존의 드루팔의 것을 사용합니다.


## 데이터베이스 구조

### post_data 테이블

이것은 post_data entity 의 기본 테이블로서 게시글을 구성하기 위한 기본 필드 외에

여유 필드가 충분히 준비되어져 있다.


* blind 는 게시글을 안보이게 할 때 사용
* block 은 게시글에 차단된 표시를 하고, 해당 사용자를 차단하는 경우 사용
* reason 은 blind 와 block 을 할 때, 그 사유를 설명
* shortcut 은 /reminder/shortcut-name 또는 "/announce/회원 등급 변경" 과 같이 짧게 지정을 하고자 할 때 사용 할 수 있다.
   
### post_history 테이블

post_history entity 의 테이블로서 게시 글에 대한 모든 종류의 행동을 기록한다.

* 찬/반 투표
* 글 신고
* 포인트의 변화

등 글에 대한 모든 정보를 기록한다.


## library_member_browser_id 테이블

사용자의 웹 브라우저에 고유 ID 를 심어 놓고 그 변화를 기록한다.

이 고유 ID 는 각 게시글에도 기록이 된다.

즉, 어떤 사용자가 어떤 이중 아이디를 쓰고, 그 모든 글을 추적 할 수 있다.

